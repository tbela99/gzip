<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\classSourceMap;

use axy\sourcemap\SourceMap;
use axy\sourcemap\PosMap;
use axy\sourcemap\PosGenerated;
use axy\sourcemap\PosSource;
use axy\sourcemap\tests\Represent;

/**
 * coversDefaultClass axy\sourcemap\SourceMap
 */
class AddTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $data = [
        'version' => 3,
        'file' => 'script.js',
        'sourceRoot' => '',
        'sources' => ['a.js', 'b.js', 'c.js'],
        'names' => ['one', 'two', 'three'],
        'mappings' => 'AAAA,YAAYA,CAACC;;;;;;;AAEbC,IAAO,GAAGD,WAAW,OAAOD,CAAC,CAACC',
    ];

    /**
     * @var \axy\sourcemap\SourceMap
     */
    private $map;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->map = new SourceMap($this->data);
    }

    /**
     * covers ::addPosition
     */
    public function testAddPosition()
    {
        $generated = new PosGenerated();
        $generated->line = 7;
        $generated->column = 15;
        $source = new PosSource();
        $source->fileIndex = 2;
        $source->line = 3;
        $source->column = 11;
        $source->name = 'two';
        $position = new PosMap($generated, $source);
        $result = $this->map->addPosition($position);
        $this->assertInstanceOf('axy\sourcemap\PosMap', $result);
        $this->assertSame($position, $result);
        $expected = [
            'generated' => [
                'line' => 7,
                'column' => 15,
            ],
            'source' => [
                'fileIndex' => 2,
                'fileName' => 'c.js',
                'line' => 3,
                'column' => 11,
                'name' => 'two',
                'nameIndex' => 1,
            ],
        ];
        $this->assertEquals($expected, Represent::posMap($position));
        $this->assertSame($position, $this->map->getPosition(7, 15));
        $eMappings = 'AAAA,YAAYA,CAACC;;;;;;;AAEbC,IAAO,GAAGD,QECCA,GFDU,OAAOD,CAAC,CAACC';
        $this->assertSame($eMappings, $this->map->getData()['mappings']);
        $this->assertTrue($this->map->removePosition(7, 15));
        $this->assertSame($this->data['mappings'], $this->map->getData()['mappings']);
    }

    /**
     * covers ::addPosition
     */
    public function testAddPositionClone()
    {
        $generated = new PosGenerated();
        $generated->line = 7;
        $generated->column = 15;
        $source = new PosSource();
        $source->fileIndex = 2;
        $source->line = 3;
        $source->column = 11;
        $source->name = 'two';
        $position = new PosMap($generated, $source);
        $result1 = $this->map->addPosition(clone $position);
        $this->assertNotSame($position, $result1);
        $this->assertNotSame($position->generated, $result1->generated);
        $this->assertEquals((array)$position->generated, (array)$result1->generated);
        $generated->line = 5;
        $result2 = $this->map->addPosition(clone $position);
        $this->assertNotSame($position->generated, $result2->generated);
        $this->assertEquals((array)$position->generated, (array)$result2->generated);
        $this->assertSame($result1, $this->map->getPosition(7, 15));
        $this->assertSame($result2, $this->map->getPosition(5, 15));
        $eMappings = 'AAAA,YAAYA,CAACC;;;;;eEGFA;;AFDXC,IAAO,GAAGD,QECCA,GFDU,OAAOD,CAAC,CAACC';
        $this->assertSame($eMappings, $this->map->getData()['mappings']);
    }

    /**
     * covers ::addPosition
     */
    public function testAddPositionArray()
    {
        $position = [
            'generated' => [
                'line' => 7,
                'column' => 15,
            ],
            'source' => [
                'fileName' => 'new.js',
                'line' => 3,
                'column' => 11,
            ],
        ];
        $result = $this->map->addPosition($position);
        $this->assertInstanceOf('axy\sourcemap\PosMap', $result);
        $expected = [
            'generated' => [
                'line' => 7,
                'column' => 15,
            ],
            'source' => [
                'fileIndex' => 3,
                'fileName' => 'new.js',
                'line' => 3,
                'column' => 11,
                'name' => null,
                'nameIndex' => null,
            ],
        ];
        $this->assertEquals($expected, Represent::posMap($result));
        $eMappings = 'AAAA,YAAYA,CAACC;;;;;;;AAEbC,IAAO,GAAGD,QGCC,GHDU,OAAOD,CAAC,CAACC';
        $this->assertSame($eMappings, $this->map->getData()['mappings']);
        $this->assertSame(['a.js', 'b.js', 'c.js', 'new.js'], $this->map->getData()['sources']);
    }

    /**
     * covers ::addPosition
     * @dataProvider providerErrorIncompleteData
     * @param mixed $position
     * @param bool $error [optional]
     */
    public function testErrorIncompleteData($position, $error = true)
    {
        if ($error) {
            $this->setExpectedException('axy\sourcemap\errors\IncompleteData');
        }
        $this->map->addPosition($position);
    }

    /**
     * @return array
     */
    public function providerErrorIncompleteData()
    {
        return [
            [null],
            [
                [
                    'generated' => ['line' => null, 'column' => null],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => null],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                ],
                false,
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0], 'source' => ['fileIndex' => 1],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'line' => 1],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'line' => 1, 'column' => 2],
                ],
                false,
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'line' => 1, 'column' => 2, 'name' => 'x'],
                ],
                false,
            ],
        ];
    }

    /**
     * covers ::addPosition
     * @dataProvider providerErrorInvalidIndexed
     * @param mixed $position
     * @param bool $error [optional]
     */
    public function testErrorInvalidIndexed($position, $error = true)
    {
        if ($error) {
            $this->setExpectedException('axy\sourcemap\errors\InvalidIndexed');
        }
        $this->map->addPosition($position);
    }

    /**
     * @return array
     */
    public function providerErrorInvalidIndexed()
    {
        return [
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 10, 'line' => 1, 'column' => 2, 'name' => 'x'],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'line' => 1, 'column' => 2, 'nameIndex' => 100],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'line' => 1, 'column' => 2, 'nameIndex' => 1, 'name' => 'zz'],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'fileName' => 'c.js', 'line' => 1, 'column' => 2],
                ],
            ],
            [
                [
                    'generated' => ['line' => 0, 'column' => 0],
                    'source' => ['fileIndex' => 1, 'fileName' => 'b.js', 'line' => 1, 'column' => 2],
                ],
                false,
            ],
        ];
    }

    public function testRenameAndAdd()
    {
        $position = [
            'generated' => [
                'line' => 7,
                'column' => 15,
            ],
            'source' => [
                'fileIndex' => 0,
                'line' => 3,
                'column' => 11,
                'nameIndex' => 2,
            ],
        ];
        $this->map->sources->rename(0, 'new.js');
        $this->map->names->rename(2, 'eval');
        $result = $this->map->addPosition($position);
        $this->assertSame('new.js', $result->source->fileName);
        $this->assertSame('eval', $result->source->name);
    }

    public function testSourcesAndNames()
    {
        $map = new SourceMap();
        $position1 = [
            'generated' => [
                'line' => 1,
                'column' => 2,
            ],
            'source' => [
                'fileName' => 'a.js',
                'line' => 5,
                'column' => 3,
            ],
        ];
        $map->addPosition($position1);
        $position2 = [
            'generated' => [
                'line' => 1,
                'column' => 12,
            ],
            'source' => [
                'fileName' => 'b.js',
                'line' => 6,
                'column' => 0,
                'name' => 'MyClass',
            ],
        ];
        $map->addPosition($position2);
        $expected = [
            'version' => 3,
            'file' => '',
            'sourceRoot' => '',
            'sources' => ['a.js', 'b.js'],
            'names' => ['MyClass'],
            'mappings' => ';EAKG,UCCHA',
        ];
        $this->assertEquals($expected, $map->getData());
    }
}
