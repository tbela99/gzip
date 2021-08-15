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
class ChangeTest extends \PHPUnit_Framework_TestCase
{
    public function testFileRename()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/app.js.map');
        $data = $map->getData();
        $map->sources->rename(0, 'new-carry.ts');
        $map->sources->rename(2, 'new-app.ts');
        $map->sources->rename(5, 'new-none.ts');
        $expected = $data;
        $expected['sources'][0] = 'new-carry.ts';
        $expected['sources'][2] = 'new-app.ts';
        $this->assertEquals($expected, $map->getData());
    }

    public function testNameRename()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/map.js.map');
        $data = $map->getData();
        $map->names->rename(0, 'One');
        $map->names->rename(5, 'Two');
        $map->names->rename(55, 'Three');
        $expected = $data;
        $expected['names'][0] = 'One';
        $expected['names'][5] = 'Two';
        $this->assertEquals($expected, $map->getData());
    }

    public function testNameRemove()
    {
        $data = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAA,YAAYA,CAACC;;;;;;;AAEbC,IAAO,GAAGD,WAAW,OAAOD,CAAC,CAACC',
        ];
        $map = new SourceMap($data);
        $map->names->remove(1);
        $map->names->remove(10);
        $expected = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts'],
            'names' => ['one', 'three'],
            'mappings' => 'AAAA,YAAYA,CAAC;;;;;;;AAEbC,IAAO,GAAG,WAAW,OAAOD,CAAC,CAAC',
        ];
        $this->assertEquals($expected, $map->getData());
    }

    public function testFileRemove()
    {
        $data = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts', 'two.ts'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAA,YAAYA,CCACC;;;;;;;AAEbC,IAAO,GDAGD,WAAW,OAAOD,CAAC,CAACC',
        ];
        $map = new SourceMap($data);
        $map->sources->remove(1);
        $map->sources->remove(10);
        $expected = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAA,YAAYA;;;;;;;OAEFC,WAAW,OAAOD,CAAC,CAACC',
        ];
        $this->assertEquals($expected, $map->getData());
    }

    /**
     * covers ::removePosition
     */
    public function testRemovePosition()
    {
        $data = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAA,YAAYA,CAACC;;;;;;;AAEbC,IAAO,GAAGD,WAAW,OAAOD,CAAC,CAACC',
        ];
        $map = new SourceMap($data);
        $this->assertTrue($map->removePosition(0, 12));
        $this->assertFalse($map->removePosition(0, 12));
        $this->assertFalse($map->removePosition(100, 12));
        $expected = [
            'version' => 3,
            'file' => 'script.js',
            'sourceRoot' => '',
            'sources' => ['script.ts'],
            'names' => ['one', 'two', 'three'],
            'mappings' => 'AAAA,aAAaC;;;;;;;AAEbC,IAAO,GAAGD,WAAW,OAAOD,CAAC,CAACC',
        ];
        $this->assertEquals($expected, $map->getData());
    }
}
