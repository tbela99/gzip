<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * I/O error
 */
class IOError extends Runtime implements Error
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'I/O error. File "{{ filename }}". {{ errorMessage }}';

    /**
     * The constructor
     *
     * @param string $filename [optional]
     * @param string $errorMessage [optional]
     * @param \Exception $previous [optional]
     * @param mixed $thrower [optional]
     */
    public function __construct($filename = null, $errorMessage = null, \Exception $previous = null, $thrower = null)
    {
        $this->filename = $filename;
        $this->errorMessage = $errorMessage;
        $message = [
            'filename' => $filename,
            'errorMessage' => $errorMessage,
        ];
        parent::__construct($message, 0, $previous, $thrower);
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
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
    private $filename;

    /**
     * @var string
     */
    private $errorMessage;
}
