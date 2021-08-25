<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\indexed;

use axy\sourcemap\errors\InvalidIndexed;
use axy\sourcemap\indexed\Names;
use axy\sourcemap\parsing\Context;
use axy\sourcemap\PosSource;

/**
 * coversDefaultClass axy\sourcemap\indexed\Names
 */
class NamesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     * covers ::getNames
     */
    public function testGetNames()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two'],
            'mappings' => 'A',
        ];
        $context = new Context($data);
        $names = new Names($context);
        $this->assertEquals(['one', 'two'], $names->getNames());
    }

    /**
     * covers ::rename
     */
    public function testRename()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two'],
            'mappings' => 'AAAAA,CAAAC',
        ];
        $context = new Context($data);
        $names = new Names($context);
        $this->assertEquals(['one', 'two'], $names->getNames());
        $pos0 = $context->getMappings()->getLines()[0]->getPositions()[0];
        $pos1 = $context->getMappings()->getLines()[0]->getPositions()[1];
        $this->assertSame('one', $pos0->source->name);
        $this->assertSame('two', $pos1->source->name);
        $this->assertTrue($names->rename('1', 'three'));
        $this->assertFalse($names->rename('1', 'three'));
        $this->assertFalse($names->rename(10, 'three'));
        $this->assertEquals(['one', 'three'], $names->getNames());
        $this->assertSame('one', $pos0->source->name);
        $this->assertSame('three', $pos1->source->name);
    }

    /**
     * covers ::fillSource
     */
    public function testFillSource()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAAA,CAAAC',
        ];
        $context = new Context($data);
        $names = new Names($context);
        $source = new PosSource();
        $source->fileIndex = 1;
        $source->line = 10;
        // empty
        $this->assertFalse($names->fillSource($source));
        $this->assertEquals(['one', 'two', 'three'], $names->getNames());
        $expected = [
            'fileIndex' => 1,
            'fileName' => null,
            'line' => 10,
            'column' => null,
            'name' => null,
            'nameIndex' => null,
        ];
        $this->assertEquals($expected, (array)$source);
        // by index
        $source->nameIndex = 1;
        $this->assertTrue($names->fillSource($source));
        $this->assertEquals(['one', 'two', 'three'], $names->getNames());
        $expected['nameIndex'] = 1;
        $expected['name'] = 'two';
        $this->assertEquals($expected, (array)$source);
        // by name (exists)
        $source->nameIndex = null;
        $source->name = 'three';
        $this->assertTrue($names->fillSource($source));
        $this->assertEquals(['one', 'two', 'three'], $names->getNames());
        $expected['nameIndex'] = 2;
        $expected['name'] = 'three';
        $this->assertEquals($expected, (array)$source);
        // by name (not exists)
        $source->nameIndex = null;
        $source->name = 'four';
        $this->assertTrue($names->fillSource($source));
        $this->assertEquals(['one', 'two', 'three', 'four'], $names->getNames());
        $expected['nameIndex'] = 3;
        $expected['name'] = 'four';
        $this->assertEquals($expected, (array)$source);
        // by name + index (success)
        $source->nameIndex = 1;
        $source->name = 'two';
        $this->assertTrue($names->fillSource($source));
        $this->assertEquals(['one', 'two', 'three', 'four'], $names->getNames());
        $expected['nameIndex'] = 1;
        $expected['name'] = 'two';
        $this->assertEquals($expected, (array)$source);
        // by invalid index
        $source->nameIndex = 10;
        $source->name = 'two';
        try {
            $names->fillSource($source);
            $this->fail('fail throw');
        } catch (InvalidIndexed $e) {
        }
        // by name + index (fail)
        $source->nameIndex = 2;
        $source->name = 'one';
        $this->setExpectedException('axy\sourcemap\errors\InvalidIndexed');
        $names->fillSource($source);
    }
}
