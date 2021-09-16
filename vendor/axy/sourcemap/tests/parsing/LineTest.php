<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\parsing;

use axy\sourcemap\parsing\Line;
use axy\sourcemap\parsing\SegmentParser;
use axy\sourcemap\parsing\Context;
use axy\sourcemap\PosMap;
use axy\sourcemap\tests\Represent;

/**
 * coversDefaultClass axy\sourcemap\parsing\Line
 */
class LineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     * covers ::getPositions
     * covers ::getLine
     */
    public function testGetPositions()
    {
        $positions = [
            10 => new PosMap(['line' => 10, 'column' => 10], null),
            15 => new PosMap(['line' => 10, 'column' => 15], null),
        ];
        $line = new Line(10, $positions);
        $this->assertEquals($positions, $line->getPositions());
        $this->assertSame(10, $line->getNum());
    }

    /**
     * covers ::loadFromPlainList
     * covers ::getPositions
     * covers ::getLine
     */
    public function testLoadFromPlainList()
    {
        $positions = [
            new PosMap(['line' => 10, 'column' => 10], null),
            new PosMap(['line' => 10, 'column' => 15], null),
        ];
        $line = Line::loadFromPlainList(10, $positions);
        $this->assertInstanceOf('axy\sourcemap\parsing\Line', $line);
        $expected = [
            10 => $positions[0],
            15 => $positions[1],
        ];
        $this->assertEquals($expected, $line->getPositions());
        $this->assertSame(10, $line->getNum());
    }

    /**
     * covers ::loadFromMappings
     */
    public function testLoadFromMappings()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $parser = new SegmentParser();
        $lMappings1 = 'AAAA,YAAY,CAAC';
        $line1 = Line::loadFromMappings(0, $lMappings1, $parser, $context);
        $this->assertInstanceOf('axy\sourcemap\parsing\Line', $line1);
        $expected1 = [
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
        ];
        $this->assertSame($expected1, Represent::line($line1));
        $lMappings2 = 'AAEb,IAAOC,GAAG,WAAWE';
        $line2 = Line::loadFromMappings(8, $lMappings2, $parser, $context);
        $this->assertInstanceOf('axy\sourcemap\parsing\Line', $line2);
        $expected2 = [
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
        ];
        $this->assertSame($expected2, Represent::line($line2));
    }

    /**
     * covers ::loadFromMappings
     * @dataProvider providerErrorLoad
     * @expectedException \axy\sourcemap\errors\InvalidMappings
     * @param string $lMappings
     */
    public function testErrorLoad($lMappings)
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three', 'four', 'five'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $parser = new SegmentParser();
        Line::loadFromMappings(2, $lMappings, $parser, $context);
    }

    /**
     * @return array
     */
    public function providerErrorLoad()
    {
        return [
            'empty' => [''],
            'count3' => ['AAA'],
            'base64' => ['A*AA'],
            'vlq' => ['AAAz'],
            'indexed' => ['AZAA'],
        ];
    }

    /**
     * covers ::__clone
     */
    public function testClone()
    {
        $positions = [
            10 => new PosMap(['line' => 10, 'column' => 10], null),
            15 => new PosMap(['line' => 10, 'column' => 15], null),
        ];
        $line = new Line(5, $positions);
        $line2 = clone $line;
        $this->assertSame(5, $line2->getNum());
        $this->assertSame(Represent::line($line), Represent::line($line2));
        $positions2 = $line2->getPositions();
        $this->assertInstanceOf('axy\sourcemap\PosMap', $positions2[15]);
        $this->assertEquals($positions[15], $positions2[15]);
        $this->assertNotSame($positions[15], $positions2[15]);
    }

    /**
     * covers ::pack
     */
    public function testPack()
    {
        $parser = new SegmentParser();
        $positions1 = [
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
        ];
        foreach ($positions1 as &$pos) {
            $pos = new PosMap($pos['generated'], $pos['source']);
        }
        unset($pos);
        $line1 = new Line(0, $positions1);
        $parser->nextLine(0);
        $this->assertSame('AAAA,YAAY,CAAC', $line1->pack($parser));
        $positions2 = [
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
        ];
        foreach ($positions2 as &$pos) {
            $pos = new PosMap($pos['generated'], $pos['source']);
        }
        unset($pos);
        $line2 = new Line(8, $positions2);
        $parser->nextLine(8);
        $this->assertSame('AAEb,IAAOC,GAAG,WAAWE', $line2->pack($parser));
    }
}
