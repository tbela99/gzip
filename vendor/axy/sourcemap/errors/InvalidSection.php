<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * The source map section has an invalid format
 */
final class InvalidSection extends Runtime implements InvalidFormat
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'Source map section "{{ section }}" is invalid: "{{ errorMessage }}"';

    /**
     * @param string $section
     * @param string $errorMessage
     * @param \Exception $previous [optional]
     * @param mixed $thrower [optional]
     */
    public function __construct($section = null, $errorMessage = null, \Exception $previous = null, $thrower = null)
    {
        $this->section = $section;
        $this->errorMessage = $errorMessage;
        $message = [
            'section' => $section,
            'errorMessage' => $errorMessage,
        ];
        parent::__construct($message, 0, $previous, $thrower);
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
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
    private $section;

    /**
     * @var string
     */
    private $errorMessage;
}
