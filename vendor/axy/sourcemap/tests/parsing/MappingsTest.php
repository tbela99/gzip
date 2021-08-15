<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\parsing;

use axy\sourcemap\parsing\Mappings;
use axy\sourcemap\parsing\Context;
use axy\sourcemap\PosMap;
use axy\sourcemap\tests\Represent;

/**
 * coversDefaultClass axy\sourcemap\parsing\Mappings
 */
class MappingsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $struct = [
        0 =>  [
            0 => [
                'generated' => [
                    'line' => 0,
                    'column' => 0,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 0,
                    'column' => 0,
                    'nameIndex' => null,
                    'name' => null,
                ],
            ],
            12 => [
                'generated' => [
                    'line' => 0,
                    'column' => 12,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 0,
                    'column' => 12,
                    'nameIndex' => null,
                    'name' => null,
                ],
            ],
            13 => [
                'generated' => [
                    'line' => 0,
                    'column' => 13,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 0,
                    'column' => 13,
                    'nameIndex' => null,
                    'name' => null,
                ],
            ],
        ],
        8 => [
            0 => [
                'generated' => [
                    'line' => 8,
                    'column' => 0,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 2,
                    'column' => 0,
                    'nameIndex' => null,
                    'name' => null,
                ],
            ],
            4 => [
                'generated' => [
                    'line' => 8,
                    'column' => 4,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 2,
                    'column' => 7,
                    'nameIndex' => 1,
                    'name' => 'two',
                ],
            ],
            7 => [
                'generated' => [
                    'line' => 8,
                    'column' => 7,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 2,
                    'column' => 10,
                    'nameIndex' => null,
                    'name' => null,
                ],
            ],
            18 => [
                'generated' => [
                    'line' => 8,
                    'column' => 18,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 2,
                    'column' => 21,
                    'nameIndex' => 3,
                    'name' => 'four',
                ],
            ],
        ],
    ];

    /**
     * covers ::__construct
     * covers ::getLines
     */
    public function testParse()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $mappings = new Mappings('AAAA,YAAY,CAAC;;;;;;;;AAEb,IAAOC,GAAG,WAAWE', $context);
        $this->assertEquals($this->struct, Represent::mappings($mappings));
    }

    /**
     * covers ::addPosition
     */
    public function testAddPosition()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $mappings = new Mappings('', $context);
        $this->assertEquals([], $mappings->getLines());
        foreach ($this->struct as $line) {
            foreach ($line as $position) {
                $mappings->addPosition(new PosMap($position['generated'], $position['source']));
            }
        }
        $this->assertEquals($this->struct, Represent::mappings($mappings));
    }

    public function testRemovePosition()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $mappings = new Mappings('AAAA,YAAY,CAAC;;;;;;;;AAEb,IAAOC,GAAG,WAAWE', $context);
        $this->assertFalse($mappings->removePosition(3, 5));
        $this->assertTrue($mappings->removePosition(8, 4));
        $this->assertFalse($mappings->removePosition(8, 4));
        $expected = $this->struct;
        unset($expected[8][4]);
        $this->assertEquals($expected, Represent::mappings($mappings));
    }

    /**
     * covers ::pack
     */
    public function testPack()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $mappings = new Mappings('', $context);
        $this->assertEquals('', $mappings->pack());
        $struct = [$this->struct[8], $this->struct[0]];
        foreach ($struct as $line) {
            foreach ($line as $position) {
                $mappings->addPosition(new PosMap($position['generated'], $position['source']));
            }
        }
        $this->assertSame('AAAA,YAAY,CAAC;;;;;;;;AAEb,IAAOC,GAAG,WAAWE', $mappings->pack());
        $generated = [
            'line' => 10,
            'column' => 4,
        ];
        $source = [
            'fileIndex' => 1,
            'fileName' => 'b.js',
            'line' => 10,
            'column' => 0,
            'nameIndex' => 1,
            'name' => 'two',
        ];
        $pos = new PosMap($generated, $source);
        $mappings->addPosition($pos);
        $mappings->removePosition(8, 7);
        $this->assertSame('AAAA,YAAY,CAAC;;;;;;;;AAEb,IAAOC,cAAcE;;ICQrBF', $mappings->pack());
    }

    /**
     * covers ::renameFile
     * covers ::renameName
     */
    public function testRenameFileAndName()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $sMappings = 'AAAA,YAAY,CAAC;;;;;;;;AAEb,IAAOC,GAAG,WAAWE';
        $mappings = new Mappings($sMappings, $context);
        $mappings->renameFile(0, 'new.js');
        $mappings->renameName('1', 'newName');
        $expected = $this->struct;
        foreach ($expected as &$l) {
            foreach ($l as &$c) {
                if ($c['source']['fileIndex'] === 0) {
                    $c['source']['fileName'] = 'new.js';
                }
                if ($c['source']['nameIndex'] === 1) {
                    $c['source']['name'] = 'newName';
                }
            }
            unset($c);
        }
        unset($l);
        $this->assertEquals($expected, Represent::mappings($mappings));
        $this->assertSame($sMappings, $mappings->pack());
    }

    /**
     * covers ::removeFiles
     */
    public function testRemoveFiles()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js', 'c.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $positions = [
            [
                'generated' => [
                    'line' => 0,
                    'column' => 0,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 0,
                    'column' => 0,
                    'nameIndex' => 0,
                    'name' => 'one',
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 3,
                ],
                'source' => [
                    'fileIndex' => 1,
                    'fileName' => 'b.js',
                    'line' => 2,
                    'column' => 2,
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 5,
                ],
                'source' => [
                    'fileIndex' => 2,
                    'fileName' => 'c.js',
                    'line' => 3,
                    'column' => 4,
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 10,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 5,
                    'column' => 10,
                ],
            ],
        ];
        $mappings = new Mappings('', $context);
        foreach ($positions as $position) {
            $mappings->addPosition(new PosMap($position['generated'], $position['source']));
        }
        $this->assertTrue($mappings->removeFile(1));
        $expected1 = [
            0 => [
                0 => [
                    'generated' => [
                        'line' => 0,
                        'column' => 0,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'a.js',
                        'line' => 0,
                        'column' => 0,
                        'nameIndex' => 0,
                        'name' => 'one',
                    ],
                ],
            ],
            2 => [
                5 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => 1,
                        'fileName' => 'c.js',
                        'line' => 3,
                        'column' => 4,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
                10 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 10,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'a.js',
                        'line' => 5,
                        'column' => 10,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected1, Represent::mappings($mappings));
        $this->assertFalse($mappings->removeFile(5));
        $this->assertSame('AAAAA;;KCGI,KDEM', $mappings->pack());
        $this->assertEquals($expected1, Represent::mappings($mappings));
        $this->assertTrue($mappings->removeFile(0));
        $expected2 = [
            2 => [
                5 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'c.js',
                        'line' => 3,
                        'column' => 4,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected2, Represent::mappings($mappings));
        $this->assertSame(';;KAGI', $mappings->pack());
    }

    /**
     * covers ::removeName
     */
    public function testRemoveName()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js', 'c.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $positions = [
            [
                'generated' => [
                    'line' => 0,
                    'column' => 0,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 0,
                    'column' => 0,
                    'nameIndex' => 0,
                    'name' => 'one',
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 3,
                ],
                'source' => [
                    'fileIndex' => 1,
                    'fileName' => 'b.js',
                    'line' => 2,
                    'column' => 2,
                    'nameIndex' => 1,
                    'name' => 'two',
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 5,
                ],
                'source' => [
                    'fileIndex' => 2,
                    'fileName' => 'c.js',
                    'line' => 3,
                    'column' => 4,
                    'nameIndex' => 4,
                    'name' => 'five',
                ],
            ],
            [
                'generated' => [
                    'line' => 2,
                    'column' => 10,
                ],
                'source' => [
                    'fileIndex' => 0,
                    'fileName' => 'a.js',
                    'line' => 5,
                    'column' => 10,
                ],
            ],
        ];
        $mappings = new Mappings('', $context);
        foreach ($positions as $position) {
            $mappings->addPosition(new PosMap($position['generated'], $position['source']));
        }
        $this->assertTrue($mappings->removeName(0));
        $expected1 = [
            0 => [
                [
                    'generated' => [
                        'line' => 0,
                        'column' => 0,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'a.js',
                        'line' => 0,
                        'column' => 0,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
            2 => [
                3 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 3,
                    ],
                    'source' => [
                        'fileIndex' => 1,
                        'fileName' => 'b.js',
                        'line' => 2,
                        'column' => 2,
                        'nameIndex' => 0,
                        'name' => 'two',
                    ],
                ],
                5 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => 2,
                        'fileName' => 'c.js',
                        'line' => 3,
                        'column' => 4,
                        'nameIndex' => 3,
                        'name' => 'five',
                    ],
                ],
                10 => [
                    'generated' => [
                        'line' => 2,
                        'column' => 10,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'a.js',
                        'line' => 5,
                        'column' => 10,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected1, Represent::mappings($mappings));
    }

    /**
     * covers ::getStat
     */
    public function testGetStat()
    {
        $data = json_decode(file_get_contents(__DIR__.'/../tst/app.js.map'), true);
        $context = new Context($data);
        $mappings = new Mappings($data['mappings'], $context);
        $expected = [
            'sources' => [
                0 => 22,
                1 => 23,
                2 => 27,
            ],
            'names' => [
                0 => 8,
            ],
        ];
        $this->assertEquals($expected, $mappings->getStat());
    }
}
