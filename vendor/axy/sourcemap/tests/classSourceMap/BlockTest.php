<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\classSourceMap;

use axy\sourcemap\SourceMap;
use axy\sourcemap\tests\Represent;

/**
 * coversDefaultClass axy\sourcemap\SourceMap
 */
class BlockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $posS = [
        [0, 5],
        [1, 3],
        [1, 8],
        [1, 10],
        [3, 4],
        [3, 8],
        [5, 10],
    ];

    /**
     * @var array
     */
    private $posList;

    /**
     * @var \axy\sourcemap\SourceMap
     */
    private $map;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->posList = [];
        $this->map = new SourceMap();
        foreach ($this->posS as $p) {
            $position = [
                'generated' => [
                    'line' => $p[0],
                    'column' => $p[1],
                ],
                'source' => [
                    'fileName' => ($p[0] > 2) ? 'b.js' : 'a.js',
                    'line' => $p[0] + 1,
                    'column' => $p[1] * 2,
                ],
            ];
            $position = $this->map->addPosition($position);
            $this->posList[$p[0].'-'.$p[1]] = (array)$position->source;
        }
    }

    /**
     * @return array
     */
    private function getActual()
    {
        $actual = [];
        foreach ($this->map->find() as $position) {
            $key = $position->generated->line.'-'.$position->generated->column;
            $actual[$key] = (array)$position->source;
        }
        return $actual;
    }

    /**
     * @param array $shiftMap
     * @return array
     */
    private function createExpected($shiftMap)
    {
        $expected = [];
        foreach ($shiftMap as $k => $v) {
            $expected[$k] = $this->posList[$v];
        }
        return $expected;
    }

    /**
     * covers ::insertBlock
     */
    public function testInsertBlockLine()
    {
        $sm = $this->map->getData()['mappings'];
        $this->map->insertBlock(0, 0, 1, 0);
        $expected = [
            '1-5' => '0-5',
            '2-3' => '1-3',
            '2-8' => '1-8',
            '2-10' => '1-10',
            '4-4' => '3-4',
            '4-8' => '3-8',
            '6-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
        $this->assertNotSame($sm, $this->map->getData()['mappings']);
    }

    /**
     * covers ::insertBlock
     */
    public function testInsertBlockInLine()
    {
        $this->map->insertBlock(3, 5, 3, 10);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '1-8' => '1-8',
            '1-10' => '1-10',
            '3-4' => '3-4',
            '3-13' => '3-8',
            '5-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::insertBlock
     */
    public function testInsertBlockMulti()
    {
        $this->map->insertBlock(1, 5, 3, 8);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '3-11' => '1-8',
            '3-13' => '1-10',
            '5-4' => '3-4',
            '5-8' => '3-8',
            '7-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
        $expectedPos = [
            'generated' => [
                'line' => 5,
                'column' => 8,
            ],
            'source' => [
                'fileIndex' => 1,
                'fileName' => 'b.js',
                'line' => 4,
                'column' => 16,
                'name' => null,
                'nameIndex' => null,
            ],
        ];
        $this->assertEquals($expectedPos, Represent::posMap($this->map->getPosition(5, 8)));
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockLine()
    {
        $this->map->removeBlock(1, 0, 2, 0);
        $expected = [
            '0-5' => '0-5',
            '2-4' => '3-4',
            '2-8' => '3-8',
            '4-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockEmptyLine()
    {
        $this->map->removeBlock(2, 0, 3, 0);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '1-8' => '1-8',
            '1-10' => '1-10',
            '2-4' => '3-4',
            '2-8' => '3-8',
            '4-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockFromLine()
    {
        $sm = $this->map->getData()['mappings'];
        $this->map->removeBlock(1, 5, 1, 10);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '1-5' => '1-10',
            '3-4' => '3-4',
            '3-8' => '3-8',
            '5-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
        $this->assertNotSame($sm, $this->map->getData()['mappings']);
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockFromLineFull()
    {
        $this->map->removeBlock(1, 2, 1, 15);
        $expected = [
            '0-5' => '0-5',
            '3-4' => '3-4',
            '3-8' => '3-8',
            '5-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockFromLineS()
    {
        $this->map->removeBlock(1, 5, 2, 10);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '2-4' => '3-4',
            '2-8' => '3-8',
            '4-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::removeBlock
     */
    public function testRemoveBlockFromLineE()
    {
        $this->map->removeBlock(2, 5, 3, 8);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '1-8' => '1-8',
            '1-10' => '1-10',
            '2-0' => '3-8',
            '4-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }

    /**
     * covers ::insertBlock
     */
    public function testRemoveBlockMulti()
    {
        $this->map->removeBlock(1, 5, 3, 6);
        $expected = [
            '0-5' => '0-5',
            '1-3' => '1-3',
            '1-2' => '3-8',
            '3-10' => '5-10',
        ];
        $this->assertEquals($this->createExpected($expected), $this->getActual());
    }
}
