<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap;

/**
 * Map a generated position to a source position
 *
 * @link https://github.com/axypro/sourcemap/blob/master/doc/PosMap.md documentation
 */
class PosMap
{
    /**
     * @var \axy\sourcemap\PosGenerated
     */
    public $generated;

    /**
     * @var \axy\sourcemap\PosSource
     */
    public $source;

    /**
     * The constructor
     *
     * @param mixed $generated
     * @param mixed $source [optional]
     * @throws \InvalidArgumentException
     */
    public function __construct($generated, $source = null)
    {
        if ($generated instanceof PosGenerated) {
            $this->generated = $generated;
        } else {
            $this->generated = new PosGenerated();
            $this->createItem($this->generated, $generated);
        }
        if ($source instanceof PosSource) {
            $this->source = $source;
        } else {
            $this->source = new PosSource();
            $this->createItem($this->source, $source);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->generated = clone $this->generated;
        $this->source = clone $this->source;
    }

    /**
     * @param object $item
     * @param mixed $data
     */
    private function createItem($item, $data)
    {
        if ($data === null) {
            return;
        }
        if ((!is_array($data)) && (!is_object($data))) {
            throw new \InvalidArgumentException('Argument for PosMap must be an array or an object');
        }
        foreach ($data as $k => $v) {
            if (property_exists($item, $k)) {
                $item->$k = $v;
            }
        }
    }
}
