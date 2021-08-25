<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\classSourceMap;

use axy\sourcemap\SourceMap;

/**
 * coversDefaultClass axy\sourcemap\SourceMap
 */
class OptimizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::optiomize
     */
    public function testOptimize()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/app.js.map');
        $filter1 = [
            'source' => [
                'fileIndex' => 1,
            ],
        ];
        $filter2 = [
            'source' => [
                'nameIndex' => 0,
            ],
        ];
        $positions = array_merge($map->find($filter1), $map->find($filter2));
        foreach ($positions as $pos) {
            $g = $pos->generated;
            $map->removePosition($g->line, $g->column);
        }
        $this->assertCount(41, $map->find());
        $mappingsB = [
            'AAIA,SAAS,KAAK,CAAC,CAAQ;;QAEf,MAAM,CAAC,CAAC,GAAG,CAAC,CAAC;',
            'IACjB,CAAC;;;AEPL,IAAI,MAAM,GAAG,KAAK,CAAC,CAAC,CAAC,GAAG,IAAI,',
            'CAAC,CAAC,CAAC,GAAG,IAAI,CAAC,CAAC,CAAC,CAAC;AAE1C,OAAO,CAAC,GAAG,CAAC,MAAM,CAAC,CAAC',
        ];
        $expectedBefore = [
            'version' => 3,
            'file' => 'app.js',
            'sourceRoot' => '',
            'sources' => ['carry.ts', 'funcs.ts', 'app.ts'],
            'names' => ['carry'],
            'mappings' => implode('', $mappingsB),
        ];
        $this->assertEquals($expectedBefore, $map->getData());
        $this->assertTrue($map->optimize());
        $mappingsA = [
            'AAIA,SAAS,KAAK,CAAC,CAAQ;;QAEf,MAAM,CAAC,CAAC,GAAG,CAAC,CAAC;'.
            'IACjB,CAAC;;;ACPL,IAAI,MAAM,GAAG,KAAK,CAAC,CAAC,CAAC,GAAG,IAAI,'.
            'CAAC,CAAC,CAAC,GAAG,IAAI,CAAC,CAAC,CAAC,CAAC;AAE1C,OAAO,CAAC,GAAG,CAAC,MAAM,CAAC,CAAC',
        ];
        $expectedAfter = [
            'version' => 3,
            'file' => 'app.js',
            'sourceRoot' => '',
            'sources' => ['carry.ts', 'app.ts'],
            'names' => [],
            'mappings' => implode('', $mappingsA),
        ];
        $this->assertEquals($expectedAfter, $map->getData());
        $this->assertFalse($map->optimize());
        $this->assertEquals($expectedAfter, $map->getData());
    }
}
