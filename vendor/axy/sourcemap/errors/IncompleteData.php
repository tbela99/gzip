<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * Input data is incomplete
 */
final class IncompleteData extends Runtime implements InvalidFormat
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'Input data is incomplete: "{{ errorMessage }}"';

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
