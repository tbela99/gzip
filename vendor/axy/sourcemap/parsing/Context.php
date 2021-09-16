<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parsing;

/**
 * Internal context of parsing and change of the source map
 */
class Context
{
    /**
     * The data of the source map file
     *
     * @var array
     */
    public $data;

    /**
     * An array from the "sources" section
     *
     * @var string[]
     */
    public $sources;

    /**
     * An array from the "names" section
     *
     * @var string[]
     */
    public $names;

    /**
     * A wrapper on "mappings" section
     *
     * @var \axy\sourcemap\parsing\mappings
     */
    public $mappings;

    /**
     * The constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->sources = isset($data['sources']) ? $data['sources'] : [];
        $this->names = isset($data['names']) ? $data['names'] : [];
        $this->mappings = isset($data['mappings_serialized']) && $data['mappings_serialized'] instanceof Mappings
            ? $data['mappings_serialized']
            : null;
    }

    /**
     * Returns the mappings wrapper
     *
     * @return \axy\sourcemap\parsing\Mappings
     */
    public function getMappings()
    {
        if ($this->mappings === null) {
            $this->mappings = new Mappings($this->data['mappings'], $this);
        }
        return $this->mappings;
    }

    public function __clone()
    {
        if ($this->mappings !== null) {
            $this->mappings = clone $this->mappings;
        }
    }
}
