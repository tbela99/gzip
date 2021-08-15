<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * The source map JSON has an invalid format
 */
final class InvalidJSON extends Runtime implements InvalidFormat
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'Source map JSON is invalid';

    /**
     * @param \Exception $previous [optional]
     * @param mixed $thrower [optional]
     */
    public function __construct(\Exception $previous = null, $thrower = null)
    {
        parent::__construct([], 0, $previous, $thrower);
    }
}
