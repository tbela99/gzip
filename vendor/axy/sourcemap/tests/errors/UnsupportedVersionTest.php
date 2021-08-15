<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\errors;

use axy\sourcemap\errors\UnsupportedVersion;

/**
 * coversDefaultClass axy\sourcemap\errors\UnsupportedVersion
 */
class UnsupportedVersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     * covers ::getVersion
     */
    public function testError()
    {
        $ep = new \RuntimeException();
        $e = new UnsupportedVersion('1.2.3', $ep);
        $this->assertSame('Source map version 1.2.3 is unsupported. Supported only 3.', $e->getMessage());
        $this->assertSame('1.2.3', $e->getVersion());
        $this->assertSame($ep, $e->getPrevious());
    }
}
