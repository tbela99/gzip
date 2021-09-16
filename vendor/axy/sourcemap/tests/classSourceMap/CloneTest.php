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
class CloneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__clone
     */
    public function testClone()
    {
        $map1 = SourceMap::loadFromFile(__DIR__.'/../tst/map.js.map');
        $pos1 = $map1->getPosition(7, 18);
        $this->assertSame(2, $pos1->source->line);
        $map2 = clone $map1;
        $this->assertNotSame($map1, $map2);
        $pos2 = $map2->getPosition(7, 18);
        $this->assertEquals(Represent::posMap($pos1), Represent::posMap($pos2));
        $this->assertNotSame($pos2, $pos1);
        $data = $map1->getData();
        $this->assertEquals($data, $map2->getData());
        $map2->sources->rename(0, 'new.js');
        $map2->names->rename(2, 'qwerty');
        $map2->file = 'new-out.js';
        $map2->removePosition(7, 18);
        $this->assertEquals($data, $map1->getData());
        $this->assertSame($pos1, $map1->getPosition(7, 18));
        $this->assertNotSame($pos2, $map2->getPosition(7, 18));
        $this->assertEquals($map1->getPosition(7, 25)->generated, $map2->getPosition(7, 25)->generated);
        $this->assertNotSame($map1->getPosition(7, 25)->generated, $map2->getPosition(7, 25)->generated);
        $expected = [
            'version' => 3,
            'file' => 'new-out.js',
            'sourceRoot' => '',
            'sources' => ['new.js'],
            'names' => ['MyClass', 'constructor', 'qwerty', 'redraw', 'CW', 'CW.constructor'],
            'mappings' => 'AAAA,YAAY,CAAC;;;;;;;AAEb,IAAO,GAAG,kBAAkB,CAAC,CAAC',
        ];
        $this->assertEquals($expected, $map2->getData());
    }
}
