<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\errors;

use axy\sourcemap\errors\OutFileNotSpecified;

/**
 * coversDefaultClass axy\sourcemap\errors\OutFileNotSpecifiedTest
 */
class OutFileNotSpecifiedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     */
    public function testError()
    {
        $ep = new \RuntimeException();
        $e = new OutFileNotSpecified($ep);
        $this->assertSame('The default file name of the map is not specified', $e->getMessage());
        $this->assertSame($ep, $e->getPrevious());
    }
}
