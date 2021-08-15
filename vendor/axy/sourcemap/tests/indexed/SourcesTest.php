<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\indexed;

use axy\sourcemap\indexed\Sources;
use axy\sourcemap\parsing\Context;
use axy\sourcemap\SourceMap;
use axy\sourcemap\PosSource;
use axy\sourcemap\errors\InvalidIndexed;

/**
 * coversDefaultClass axy\sourcemap\indexed\Sources
 */
class SourcesTest extends \PHPUnit_Framework_TestCase
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
        $sources = new Sources($context);
        $this->assertEquals(['a.js', 'b.js'], $sources->getNames());
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
            'mappings' => 'AAAAA,CCAAC',
        ];
        $context = new Context($data);
        $sources = new Sources($context);
        $this->assertEquals(['a.js', 'b.js'], $sources->getNames());
        $pos0 = $context->getMappings()->getLines()[0]->getPositions()[0];
        $pos1 = $context->getMappings()->getLines()[0]->getPositions()[1];
        $this->assertSame('a.js', $pos0->source->fileName);
        $this->assertSame('b.js', $pos1->source->fileName);
        $sources->rename(0, 'c.js');
        $this->assertEquals(['c.js', 'b.js'], $sources->getNames());
        $this->assertSame('c.js', $pos0->source->fileName);
        $this->assertSame('b.js', $pos1->source->fileName);
    }

    /**
     * covers ::fillSource
     */
    public function testFillSource()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js', 'c.js'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAAA,CAAAC',
        ];
        $context = new Context($data);
        $s = new Sources($context);
        $source = new PosSource();
        $source->nameIndex = 1;
        $source->line = 10;
        // empty
        $this->assertFalse($s->fillSource($source));
        $this->assertEquals(['a.js', 'b.js', 'c.js'], $s->getNames());
        $expected = [
            'fileIndex' => null,
            'fileName' => null,
            'line' => 10,
            'column' => null,
            'name' => null,
            'nameIndex' => 1,
        ];
        $this->assertEquals($expected, (array)$source);
        // by index
        $source->fileIndex = 1;
        $this->assertTrue($s->fillSource($source));
        $this->assertEquals(['a.js', 'b.js', 'c.js'], $s->getNames());
        $expected['fileIndex'] = 1;
        $expected['fileName'] = 'b.js';
        $this->assertEquals($expected, (array)$source);
        // by name (exists)
        $source->fileIndex = null;
        $source->fileName = 'c.js';
        $this->assertTrue($s->fillSource($source));
        $this->assertEquals(['a.js', 'b.js', 'c.js'], $s->getNames());
        $expected['fileIndex'] = 2;
        $expected['fileName'] = 'c.js';
        $this->assertEquals($expected, (array)$source);
        // by name (not exists)
        $source->fileIndex = null;
        $source->fileName = 'd.js';
        $this->assertTrue($s->fillSource($source));
        $this->assertEquals(['a.js', 'b.js', 'c.js', 'd.js'], $s->getNames());
        $expected['fileIndex'] = 3;
        $expected['fileName'] = 'd.js';
        $this->assertEquals($expected, (array)$source);
        // by name + index (success)
        $source->fileIndex = 1;
        $source->fileName = 'b.js';
        $this->assertTrue($s->fillSource($source));
        $this->assertEquals(['a.js', 'b.js', 'c.js', 'd.js'], $s->getNames());
        $expected['fileIndex'] = 1;
        $expected['fileName'] = 'b.js';
        $this->assertEquals($expected, (array)$source);
        // by invalid index
        $source->fileIndex = 10;
        $source->fileName = 'c.js';
        try {
            $s->fillSource($source);
            $this->fail('fail throw');
        } catch (InvalidIndexed $e) {
        }
        // by name + index (fail)
        $source->fileIndex = 2;
        $source->fileName = 'a.js';
        $this->setExpectedException('axy\sourcemap\errors\InvalidIndexed');
        $s->fillSource($source);
    }

    /**
     * covers ::setContent
     * covers ::getContents
     */
    public function testContents()
    {
        $data = [
            'version' => 3,
            'sources' => ['a.js', 'b.js', 'c.js', 'd.js', 'e.js'],
            'names' => ['one',],
            'mappings' => 'AAAAA,CAAA',
        ];
        $map = new SourceMap($data);
        $sources = $map->sources;
        $this->assertEquals([], $sources->getContents());
        $sources->setContent('c.js', 'Content of C');
        $this->assertEquals([null, null, 'Content of C'], $sources->getContents());
        $sources->setContent('a.js', 'Content of A');
        $sources->setContent('e.js', 'Content of E');
        $sources->rename(0, 'z.js');
        $sources->remove(4);
        $sources->add('z.js');
        $sources->add('f.js');
        $expected = [
            'version' => 3,
            'file' => '',
            'sourceRoot' => '',
            'sources' => ['z.js', 'b.js', 'c.js', 'd.js', 'f.js'],
            'names' => ['one',],
            'mappings' => 'AAAAA,CAAA',
            'sourcesContent' => ['Content of A', null, 'Content of C'],
        ];
        $this->assertEquals($expected, $map->getData());
        $sources->setContent('h.js', 'HH');
        $expected2 = [
            'version' => 3,
            'file' => '',
            'sourceRoot' => '',
            'sources' => ['z.js', 'b.js', 'c.js', 'd.js', 'f.js', 'h.js'],
            'names' => ['one',],
            'mappings' => 'AAAAA,CAAA',
            'sourcesContent' => ['Content of A', null, 'Content of C', null, null, 'HH'],
        ];
        $this->assertEquals($expected2, $map->getData());
    }
}
