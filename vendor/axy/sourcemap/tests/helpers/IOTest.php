<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\helpers;

use axy\sourcemap\helpers\IO;

/**
 * coversDefaultClass axy\sourcemap\helpers\IO
 */
class IOTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::load
     */
    public function testLoad()
    {
        $content = IO::load(__DIR__.'/../tst/invalid.json.js.map');
        $this->assertSame('qwerty', trim($content));
    }

    /**
     * covers ::load
     * @expectedException \axy\sourcemap\errors\IOError
     * @expectedExceptionMessage not.found.js.map
     */
    public function testLoadIOError()
    {
        IO::load(__DIR__.'/../not.found.js.map');
    }

    /**
     * covers ::save
     */
    public function testSave()
    {
        $fn = __DIR__.'/../tmp/test.txt';
        $content = 'The test content';
        if (is_file($fn)) {
            unlink($fn);
        }
        IO::save($fn, $content);
        $this->assertFileExists($fn);
        $this->assertStringEqualsFile($fn, $content);
    }

    /**
     * covers ::load
     * @expectedException \axy\sourcemap\errors\IOError
     * @expectedExceptionMessage tmp/und/und.txt
     */
    public function testSaveIOError()
    {
        $fn = __DIR__.'/../tmp/und/und.txt';
        IO::save($fn, 'Content');
    }
}
