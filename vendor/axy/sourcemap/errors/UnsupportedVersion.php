<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\errors;

use axy\errors\Runtime;

/**
 * The specified source map version is unsupported
 */
final class UnsupportedVersion extends Runtime implements InvalidFormat
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'Source map version {{ version }} is unsupported. Supported only {{ supported }}.';

    /**
     * @param mixed $version
     * @param \Exception $previous [optional]
     * @param mixed $thrower [optional]
     */
    public function __construct($version, \Exception $previous = null, $thrower = null)
    {
        $this->version = $version;
        $message = [
            'version' => $version,
            'supported' => implode(', ', $this->supported),
        ];
        parent::__construct($message, 0, $previous, $thrower);
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @var string
     */
    private $version;

    /**
     * @var int[]
     */
    private $supported = [3];
}
