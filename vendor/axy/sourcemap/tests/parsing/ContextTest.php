<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\parsing;

use axy\sourcemap\parsing\Context;
use axy\sourcemap\tests\Represent;

/**
 * coversDefaultClass axy\sourcemap\parsing\Context
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     */
    public function testCreate()
    {
        $data = [
            'version' => 3,
            'file' => 'out.js',
            'sources' => ['a.js'],
            'names' => [],
            'mappings' => 'AAAC',
        ];
        $context = new Context($data);
        $this->assertEquals($data, $context->data);
        $this->assertEquals($data['sources'], $context->sources);
        $this->assertEquals($data['names'], $context->names);
        $this->assertInstanceOf('axy\sourcemap\parsing\Mappings', $context->getMappings());
        $expectedMappings = [
            0 => [
                0 => [
                    'generated' => [
                        'line' => 0,
                        'column' => 0,
                    ],
                    'source' => [
                        'fileIndex' => 0,
                        'fileName' => 'a.js',
                        'line' => 0,
                        'column' => 1,
                        'name' => null,
                        'nameIndex' => null,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedMappings, Represent::mappings($context->getMappings()));
    }
}
