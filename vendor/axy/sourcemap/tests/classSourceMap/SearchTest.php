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
class SearchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::getPosition
     */
    public function testGetPosition()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/app.js.map');
        $pos = $map->getPosition(1, 21);
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos);
        $expected = [
            'generated' => [
                'line' => 1,
                'column' => 21,
            ],
            'source' => [
                'fileIndex' => 0,
                'fileName' => 'carry.ts',
                'line' => 5,
                'column' => 20,
                'nameIndex' => 0,
                'name' => 'carry',
            ],
        ];
        $this->assertEquals($expected, Represent::posMap($pos));
        $this->assertSame($pos, $map->getPosition(1, 21));
        $this->assertNull($map->getPosition(2, 22));
        $this->assertNull($map->getPosition(200, 10));
    }

    /**
     * covers ::findPositionInSource
     */
    public function testFindPositionInSource()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/app.js.map');
        $pos = $map->findPositionInSource(1, 1, 35);
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos);
        $expected = [
            'generated' => [
                'line' => 5,
                'column' => 37,
            ],
            'source' => [
                'fileIndex' => 1,
                'fileName' => 'funcs.ts',
                'line' => 1,
                'column' => 35,
                'nameIndex' => null,
                'name' => null,
            ],
        ];
        $this->assertEquals($expected, Represent::posMap($pos));
        $this->assertSame($pos, $map->findPositionInSource(1, 1, 35));
        $this->assertNull($map->findPositionInSource(1, 1, 36));
        $this->assertNull($map->findPositionInSource(1, 100, 36));
        $this->assertNull($map->findPositionInSource(100, 1, 36));
    }

    /**
     * covers ::find
     */
    public function testFind()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/app.js.map');
        $all = $map->find();
        $this->assertInternalType('array', $all);
        $this->assertCount(72, $all);
        $p23 = $all[23];
        $this->assertInstanceOf('axy\sourcemap\PosMap', $p23);
        $expected23 = [
            'generated' => [
                'line' => 5,
                'column' => 4,
            ],
            'source' => [
                'fileIndex' => 1,
                'fileName' => 'funcs.ts',
                'line' => 0,
                'column' => 4,
                'nameIndex' => null,
                'name' => null,
            ],
        ];
        $this->assertEquals($expected23, Represent::posMap($p23));
        $filter = [
            'generated' => [
                'line' => 3,
            ],
            'source' => [
                'line' => 7,
                'name' => 'carry',
            ],
        ];
        $result = $map->find($filter);
        $this->assertCount(1, $result);
        $this->assertSame($map->getPosition(3, 6), $result[0]);
        $this->assertEmpty($map->find(['generated' => ['line' => 100]]));
    }
}
