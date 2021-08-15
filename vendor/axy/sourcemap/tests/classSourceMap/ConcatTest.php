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
class ConcatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::concat
     */
    public function testConcat()
    {
        $sizes = [
            'a' => 3,
            'bc' => 6,
            'd' => 3,
        ];
        $map = new SourceMap();
        $map->file = 'out.js';
        $line = 0;
        foreach ($sizes as $name => $qLines) {
            $filename = __DIR__.'/../tst/concat/'.$name.'.js.map';
            $map->concat($filename, $line);
            $line += $qLines;
        }
        $expectedPos = [
            0 => [
                0 => '0,0,0,',
                9 => '0,0,9,',
                10 => '0,0,10,',
            ],
            1 => [
                4 => '0,1,4,0',
                10 => '0,1,10,0',
                11 => '0,1,11,0',
                14 => '0,1,14,0',
                15 => '0,1,15,0',
            ],
            2 => [
                0 => '0,2,0,0',
                1 => '0,2,1,0',
            ],
            3 => [
                0 => '1,0,0,',
                9 => '1,0,9,',
                10 => '1,0,10,',
            ],
            4 => [
                4 => '1,1,4,1',
                10 => '1,1,10,1',
                11 => '1,1,11,1',
                14 => '1,1,14,1',
                15 => '1,1,15,1',
            ],
            5 => [
                0 => '1,2,0,1',
                1 => '1,2,1,1',
            ],
            6 => [
                0 => '2,0,0,',
                9 => '2,0,9,',
                10 => '2,0,10,',
            ],
            7 => [
                '4' => '2,1,4,2',
                '10' => '2,1,10,2',
                '11' => '2,1,11,2',
                '14' => '2,1,14,2',
                '17' => '2,1,17,2',
                '18' => '2,1,18,2',
                '20' => '2,1,20,2',
                '21' => '2,1,21,2',
            ],
            8 => [
                0 => '2,2,0,2',
                1 => '2,2,1,2',
            ],
            9 => [
                0 => '3,0,0,',
                9 => '3,0,9,',
                10 => '3,0,10,',
            ],
            10 => [
                4 => '3,1,4,3',
                10 => '3,1,10,3',
                11 => '3,1,11,3',
                14 => '3,1,14,3',
                15 => '3,1,15,3',
            ],
            11 => [
                0 => '3,2,0,3',
                1 => '3,2,1,3',
            ],
        ];
        $this->assertEquals($expectedPos, Represent::shortMap($map));
        $expectedPos = [
            'generated' => [
                'line' => 8,
                'column' => 1,
            ],
            'source' => [
                'fileIndex' => 2,
                'fileName' => 'c.ts',
                'line' => 2,
                'column' => 1,
                'nameIndex' => 2,
                'name' => 'c',
            ],
        ];
        $this->assertEquals($expectedPos, Represent::posMap($map->getPosition(8, 1)));
        $mappings = [
            'AAAA,SAAS,CAAC;IACNA,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA;ACFD,SAAS,CAAC;',
            'IACNC,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA;ACFD,SAAS,CAAC;IACNC,MAAMA,CAACA,',
            'GAAGA,GAAGA,CAACA,EAAEA,CAACA;AACrBA,CAACA;ACFD,SAAS,CAAC;',
            'IACNC,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA',
        ];
        $expectedData = [
            'version' => 3,
            'file' => 'out.js',
            'sourceRoot' => '',
            'sources' => ['a.ts', 'b.ts', 'c.ts', 'd.ts'],
            'names' => ['a', 'b', 'c', 'd'],
            'mappings' => implode('', $mappings),
        ];
        $this->assertEquals($expectedData, $map->getData());
    }

    /**
     * covers ::concat
     */
    public function testConcatLine()
    {
        $sizes = [
            'a' => 3,
            'bc' => 6,
            'd' => 3,
        ];
        $map = new SourceMap();
        $map->file = 'out.js';
        $line = 0;
        foreach ($sizes as $name => $qLines) {
            $filename = __DIR__.'/../tst/concat/'.$name.'.js.map';
            $map->concat($filename, $line, $line ? 50 : 0);
            $line += $qLines - 1;
        }
        $expectedPos = [
            0 => [
                0 => '0,0,0,',
                9 => '0,0,9,',
                10 => '0,0,10,',
            ],
            1 => [
                4 => '0,1,4,0',
                10 => '0,1,10,0',
                11 => '0,1,11,0',
                14 => '0,1,14,0',
                15 => '0,1,15,0',
            ],
            2 => [
                0 => '0,2,0,0',
                1 => '0,2,1,0',
                50 => '1,0,0,',
                59 => '1,0,9,',
                60 => '1,0,10,',
            ],
            3 => [
                4 => '1,1,4,1',
                10 => '1,1,10,1',
                11 => '1,1,11,1',
                14 => '1,1,14,1',
                15 => '1,1,15,1',
            ],
            4 => [
                0 => '1,2,0,1',
                1 => '1,2,1,1',
            ],
            5 => [
                0 => '2,0,0,',
                9 => '2,0,9,',
                10 => '2,0,10,',
            ],
            6 => [
                '4' => '2,1,4,2',
                '10' => '2,1,10,2',
                '11' => '2,1,11,2',
                '14' => '2,1,14,2',
                '17' => '2,1,17,2',
                '18' => '2,1,18,2',
                '20' => '2,1,20,2',
                '21' => '2,1,21,2',
            ],
            7 => [
                0 => '2,2,0,2',
                1 => '2,2,1,2',
                50 => '3,0,0,',
                59 => '3,0,9,',
                60 => '3,0,10,',
            ],
            8 => [
                4 => '3,1,4,3',
                10 => '3,1,10,3',
                11 => '3,1,11,3',
                14 => '3,1,14,3',
                15 => '3,1,15,3',
            ],
            9 => [
                0 => '3,2,0,3',
                1 => '3,2,1,3',
            ],
        ];
        $this->assertEquals($expectedPos, Represent::shortMap($map));
        $expectedPos = [
            'generated' => [
                'line' => 7,
                'column' => 1,
            ],
            'source' => [
                'fileIndex' => 2,
                'fileName' => 'c.ts',
                'line' => 2,
                'column' => 1,
                'nameIndex' => 2,
                'name' => 'c',
            ],
        ];
        $this->assertEquals($expectedPos, Represent::posMap($map->getPosition(7, 1)));
        $mappings = [
            'AAAA,SAAS,CAAC;IACNA,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA,iDCFD,SAAS,CAAC;',
            'IACNC,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA;ACFD,SAAS,CAAC;IACNC,MAAMA,CAACA,GAAGA,GAAGA,',
            'CAACA,EAAEA,CAACA;AACrBA,CAACA,iDCFD,SAAS,CAAC;IACNC,MAAMA,CAACA,GAAGA,CAACA;AACfA,CAACA',
        ];
        $expectedData = [
            'version' => 3,
            'file' => 'out.js',
            'sourceRoot' => '',
            'sources' => ['a.ts', 'b.ts', 'c.ts', 'd.ts'],
            'names' => ['a', 'b', 'c', 'd'],
            'mappings' => implode('', $mappings),
        ];
        $this->assertEquals($expectedData, $map->getData());
    }


    /**
     * covers ::concat
     *
     * @expectedException \axy\sourcemap\errors\InvalidJSON
     */
    public function testConcatInvalidFormat()
    {
        $map = new SourceMap();
        $map->concat(__DIR__.'/../tst/invalid.json.js.map', 0);
    }

    public function testConcatSourcesContent()
    {
        $file_path_a = __DIR__.'/../tst/concat/a_content.js.map';
        $file_path_b = __DIR__.'/../tst/concat/bc_content.js.map';
        $map = SourceMap::loadFromFile($file_path_a);
        $map->concat($file_path_b, 1);
        $raw_map_a = json_decode(file_get_contents($file_path_a));
        $raw_map_b = json_decode(file_get_contents($file_path_b));
        $all_sources_raw = array_merge($raw_map_a->sourcesContent, $raw_map_b->sourcesContent);
        $serialized = json_encode($map);
        $total_sources_content_count = count($raw_map_a->sourcesContent) + count($raw_map_b->sourcesContent);
        $this->assertSame(json_decode($serialized)->sourcesContent, $all_sources_raw);
    }
}
