<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\helpers;

use axy\sourcemap\helpers\MapBuilder;
use axy\sourcemap\SourceMap;

/**
 * coversDefaultClass axy\sourcemap\helpers\MapsBuilder
 */
class MapBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::build
     * @dataProvider providerBuild
     * @param mixed $pointer
     * @param string $file
     * @param string $exception [optional]
     */
    public function testBuild($pointer, $file, $exception = null)
    {
        if ($exception === null) {
            $map = MapBuilder::build($pointer);
            $this->assertInstanceOf('axy\sourcemap\SourceMap', $map);
            $this->assertSame($file, $map->file);
        } else {
            $this->setExpectedException($exception);
            MapBuilder::build($pointer);
        }
    }

    /**
     * @return array
     */
    public function providerBuild()
    {
        $data = [
            'version' => 3,
            'file' => 'out.js',
            'sources' => [],
            'names' => [],
            'mappings' => 'A',
        ];
        $data2 = $data;
        $data2['file'] = 'file.js';
        return [
            [
                new SourceMap($data),
                'out.js',
            ],
            [
                $data2,
                'file.js',
            ],
            [
                __DIR__.'/../tst/map.js.map',
                'script.js',
            ],
            [
                __DIR__.'/../tst/notFound.js.map',
                null,
                'axy\sourcemap\errors\IOError',
            ],
            [
                __DIR__.'/../tst/invalid.json.js.map',
                null,
                'axy\sourcemap\errors\InvalidFormat',
            ],
            [
                5,
                null,
                'InvalidArgumentException',
            ],
        ];
    }
}
