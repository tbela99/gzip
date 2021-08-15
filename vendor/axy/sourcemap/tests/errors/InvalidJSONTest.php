<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\errors;

use axy\sourcemap\errors\InvalidJSON;

/**
 * coversDefaultClass axy\sourcemap\errors\InvalidJSON
 */
class InvalidJSONTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     */
    public function testError()
    {
        $ep = new \RuntimeException();
        $e = new InvalidJSON($ep);
        $this->assertSame('Source map JSON is invalid', $e->getMessage());
        $this->assertSame($ep, $e->getPrevious());
    }
}
