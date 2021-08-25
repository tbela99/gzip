<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\helpers;

use axy\sourcemap\helpers\PosBuilder;
use axy\sourcemap\tests\Represent;
use axy\sourcemap\PosMap;

/**
 * coversDefaultClass axy\sourcemap\helpers\PosBuilder
 */
class PosBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::build
     * @dataProvider providerBuild
     * @param mixed $p
     * @param array $expected
     */
    public function testBuild($p, $expected)
    {
        $pos = PosBuilder::build($p);
        $this->assertInstanceOf('axy\sourcemap\PosMap', $pos);
        $this->assertEquals($expected, Represent::posMap($pos));
    }

    /**
     * @return array
     */
    public function providerBuild()
    {
        return [
            [
                new PosMap(['line' => 3, 'column' => 5], ['fileIndex' => 3, 'line' => 10]),
                [
                    'generated' => [
                        'line' => 3,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => 3,
                        'fileName' => null,
                        'line' => 10,
                        'column' => null,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
            [
                ['generated' => ['line' => 3, 'column' => 5], 'source' => ['fileIndex' => 3, 'line' => 10]],
                [
                    'generated' => [
                        'line' => 3,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => 3,
                        'fileName' => null,
                        'line' => 10,
                        'column' => null,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
            [
                (object)['generated' => (object)['line' => 3, 'column' => 5]],
                [
                    'generated' => [
                        'line' => 3,
                        'column' => 5,
                    ],
                    'source' => [
                        'fileIndex' => null,
                        'fileName' => null,
                        'line' => null,
                        'column' => null,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
            [
                null,
                [
                    'generated' => [
                        'line' => null,
                        'column' => null,
                    ],
                    'source' => [
                        'fileIndex' => null,
                        'fileName' => null,
                        'line' => null,
                        'column' => null,
                        'nameIndex' => null,
                        'name' => null,
                    ],
                ],
            ],
        ];
    }
}
