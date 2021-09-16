<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests;

use axy\sourcemap\PosMap;
use axy\sourcemap\PosGenerated;
use axy\sourcemap\PosSource;

/**
 * coversDefaultClass axy\sourcemap\PosMap
 */
class PosMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     * @dataProvider providerCreate
     * @param mixed $cGenerated
     * @param mixed $cSource
     * @param array $eGenerated
     * @param array $eSource
     */
    public function testCreate($cGenerated, $cSource, $eGenerated, $eSource)
    {
        $posMap = new PosMap($cGenerated, $cSource);
        $aGenerated = $posMap->generated;
        $aSource = $posMap->source;
        $this->assertInstanceOf('axy\sourcemap\PosGenerated', $aGenerated);
        $this->assertInstanceOf('axy\sourcemap\PosSource', $aSource);
        $this->assertEquals($eGenerated, (array)$aGenerated);
        $this->assertEquals($eSource, (array)$aSource);
    }

    /**
     * @return array
     */
    public function providerCreate()
    {
        $g = new PosGenerated();
        $g->line = 10;
        $g->column = 20;
        $s = new PosSource();
        $s->fileIndex = 5;
        $s->line = 5;
        $s->column = 3;
        return [
            [
                $g,
                $s,
                [
                    'line' => 10,
                    'column' => 20,
                ],
                [
                    'fileIndex' => 5,
                    'fileName' => null,
                    'line' => 5,
                    'column' => 3,
                    'name' => null,
                    'nameIndex' => null,
                ],
            ],
            [
                $g,
                null,
                [
                    'line' => 10,
                    'column' => 20,
                ],
                [
                    'fileIndex' => null,
                    'fileName' => null,
                    'line' => null,
                    'column' => null,
                    'name' => null,
                    'nameIndex' => null,
                ],
            ],
            [
                ['line' => 12, 'column' => 25, 'unknown' => 30],
                ['fileName' => 'x.js', 'name' => 'for'],
                [
                    'line' => 12,
                    'column' => 25,
                ],
                [
                    'fileIndex' => null,
                    'fileName' => 'x.js',
                    'line' => null,
                    'column' => null,
                    'name' => 'for',
                    'nameIndex' => null,
                ],
            ],
            [
                (object)['line' => 12, 'column' => 25],
                (object)['fileName' => 'x.js', 'name' => 'for'],
                [
                    'line' => 12,
                    'column' => 25,
                ],
                [
                    'fileIndex' => null,
                    'fileName' => 'x.js',
                    'line' => null,
                    'column' => null,
                    'name' => 'for',
                    'nameIndex' => null,
                ],
            ],
        ];
    }

    /**
     * covers ::__construct
     * @expectedException \InvalidArgumentException
     * @return \axy\sourcemap\PosMap
     */
    public function testCreateInvalidArgument()
    {
        return new PosMap(3);
    }

    /**
     * covers ::__clone
     */
    public function testClone()
    {
        $map = new PosMap(['line' => 5], ['line' => 10, 'nameIndex' => 15]);
        $mapC = clone $map;
        $this->assertNotSame($map, $mapC);
        $this->assertNotSame($map->generated, $mapC->generated);
        $this->assertNotSame($map->source, $mapC->source);
        $this->assertEquals($map->generated, $mapC->generated);
        $this->assertEquals($map->source, $mapC->source);
    }
}
