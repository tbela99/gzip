<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests\errors;

use axy\sourcemap\errors\InvalidSection;

/**
 * coversDefaultClass axy\sourcemap\errors\InvalidSection
 */
class InvalidSectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * covers ::__construct
     * covers ::getSection
     * covers ::getErrorMessage
     */
    public function testError()
    {
        $ep = new \RuntimeException();
        $e = new InvalidSection('source', 'must be an array', $ep);
        $this->assertSame('Source map section "source" is invalid: "must be an array"', $e->getMessage());
        $this->assertSame('source', $e->getSection());
        $this->assertSame('must be an array', $e->getErrorMessage());
        $this->assertSame($ep, $e->getPrevious());
    }
}
