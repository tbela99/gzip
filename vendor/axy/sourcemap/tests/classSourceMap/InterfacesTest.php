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
class InterfacesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $data = [
        'version' => 3,
        'file' => 'script.js',
        'sourceRoot' => '/js/',
        'sources' => ['a.js', 'b.js'],
        'names' => ['one', 'two', 'three', 'four', 'five', 'six'],
        'mappings' => 'AAAA,YAAY,CAAC;;;;;;;AAEb,IAAO,GAAG,WAAW,OAAO,CAAC,CAAC',
    ];

    /**
     * @var \axy\sourcemap\SourceMap
     */
    private $map;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->map = new SourceMap($this->data);
    }

    public function testIterator()
    {
        $actual = [];
        foreach ($this->map as $k => $v) {
            $actual[$k] = $v;
        }
        $this->assertEquals($this->data, $actual);
    }

    public function testArrayAccess()
    {
        $this->assertTrue(isset($this->map['file']));
        $this->assertFalse(isset($this->map['notFile']));
        $this->assertSame('script.js', $this->map['file']);
        $this->map['file'] = 'out.js';
        $this->assertSame('out.js', $this->map['file']);
        $this->assertSame('out.js', $this->map->file);
        $this->setExpectedException('axy\errors\ReadOnly');
        unset($this->map['file']);
    }

    public function testJsonSerializable()
    {
        $json = json_encode($this->map);
        $this->assertEquals($this->data, json_decode($json, true));
    }

    public function testSerializable()
    {
        $structure = ['map' => $this->map];
        $serialized = serialize($structure);
        $unSerialized = unserialize($serialized);
        $this->assertInternalType('array', $unSerialized);
        $this->assertArrayHasKey('map', $unSerialized);
        /** @var \axy\sourcemap\SourceMap $map2 */
        $map2 = $unSerialized['map'];
        $this->assertInstanceOf('axy\sourcemap\SourceMap', $map2);
        $this->assertNotSame($this->map, $map2);
        $this->assertEquals($this->map->getData(), $map2->getData());
    }

    public function testCountable()
    {
        $this->assertSame(count($this->map->getData()), count($this->map));
    }
}
