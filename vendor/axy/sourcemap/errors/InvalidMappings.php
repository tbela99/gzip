<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * The source map mappings has an invalid format
 */
final class InvalidMappings extends Runtime implements InvalidFormat
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'Source map mappings is invalid: "{{ errorMessage }}"';

    /**
     * @param string $errorMessage
     * @param \Exception $previous [optional]
     * @param mixed $thrower [optional]
     */
    public function __construct($errorMessage = null, \Exception $previous = null, $thrower = null)
    {
        $this->errorMessage = $errorMessage;
        $message = [
            'errorMessage' => $errorMessage,
        ];
        parent::__construct($message, 0, $previous, $thrower);
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @var string
     */
    private $errorMessage;
}
