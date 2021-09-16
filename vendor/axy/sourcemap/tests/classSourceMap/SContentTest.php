<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\classSourceMap;

use axy\sourcemap\SourceMap;

class SContentTest extends \PHPUnit_Framework_TestCase
{
    public function testSourceContentLoad()
    {
        $map = SourceMap::loadFromFile(__DIR__.'/../tst/scontent.js.map');
        $fn = __DIR__.'/../tmp/scontent.js.map';
        $map->save($fn);
        $json = json_decode(file_get_contents($fn), true);
        $this->assertArrayHasKey('sourcesContent', $json);
        $this->assertSame(['The first file', 'The second file'], $json['sourcesContent']);
        $map->sources->setContent('script.ts', 'new content');
        $map->save(__DIR__.'/../tmp/scontent.js.map');
        $json = json_decode(file_get_contents($fn), true);
        $this->assertSame(['new content', 'The second file'], $json['sourcesContent']);
    }

    public function testSourceContentJsonEncode()
    {
        $filePath = __DIR__.'/../tst/scontent.js.map';
        $map = SourceMap::loadFromFile($filePath);
        $raw_map = json_decode(file_get_contents($filePath));
        $serialized = json_encode($map);
        $this->assertSame(json_decode($serialized)->sourcesContent, $raw_map->sourcesContent);
    }
}
