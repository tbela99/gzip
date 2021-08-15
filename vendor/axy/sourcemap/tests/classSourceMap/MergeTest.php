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
class MergeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::merge
     */
    public function testMerge()
    {
        $dir = __DIR__.'/../tst/merge/';
        $map = SourceMap::loadFromFile($dir.'out.js.map');
        $this->assertTrue($map->merge($dir.'ab.js.map'));
        $this->assertTrue($map->merge($dir.'cd.js.map'));
        $this->assertFalse($map->merge($dir.'ab.js.map'));
        $expectedPositions = [
            0 => [
                0 => '0,0,0,',
                8 => '0,0,9,',
                12 => '0,0,12,',
                15 => '0,1,4,0',
                21 => '0,1,11,0',
                25 => '2,0,0,',
                33 => '2,0,9,',
                37 => '2,0,12,',
                40 => '2,1,4,1',
                46 => '2,1,11,1',
                50 => ',,,',
                51 => '1,2,0,',
                59 => '1,2,9,',
                65 => '1,2,14,',
                68 => '1,3,4,2',
                74 => '1,3,11,2',
                78 => '1,3,17,2',
                84 => '3,0,0,',
                92 => '3,0,9,',
                97 => '3,0,13,',
                100 => '3,1,4,3',
                106 => '3,1,11,3',
            ],
        ];
        $this->assertEquals($expectedPositions, Represent::shortMap($map));
        $data = $map->getData();
        $expectedData = [
            'version' => 3,
            'file' => 'out.js',
            'sourceRoot' => '',
            'sources' => ['a.ts', 'c.ts', 'b.ts', 'd.ts'],
            'names' => ['one', 'two', 'three', 'four'],
            'mappings' => $data['mappings'],
        ];
        $this->assertEquals($expectedData, $data);
        $expectedPosition = [
            'generated' => [
                'line' => 0,
                'column' => 78,
            ],
            'source' => [
                'fileIndex' => 1,
                'fileName' => 'c.ts',
                'line' => 3,
                'column' => 17,
                'nameIndex' => 2,
                'name' => 'three',
            ],
        ];
        $this->assertEquals($expectedPosition, Represent::posMap($map->getPosition(0, 78)));
    }

    /**
     * covers ::merge
     * @expectedException \axy\sourcemap\errors\InvalidJSON
     */
    public function testMergeInvalidFormat()
    {
        $map = new SourceMap();
        $map->merge(__DIR__.'/../tst/invalid.json.js.map');
    }
}
