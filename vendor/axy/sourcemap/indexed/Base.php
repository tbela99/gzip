<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\indexed;

use axy\sourcemap\parsing\Context;
use axy\sourcemap\errors\InvalidIndexed;

/**
 * Basic class of indexed section ("sources" and "names")
 */
abstract class Base
{
    /**
     * The constructor
     *
     * @param \axy\sourcemap\parsing\Context $context
     *        the parsing context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
        $key = $this->contextKey;
        $this->names = $context->$key;
        $this->indexes = array_flip($this->names);
    }

    /**
     * Returns the names list
     *
     * @return string[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * Returns a name by an index (or NULL if index is not found)
     *
     * @param int $index
     * @return string|null
     */
    public function getNameByIndex($index)
    {
        return isset($this->names[$index]) ? $this->names[$index] : null;
    }

    /**
     * Returns an index by a name (or NULL if index is not found)
     *
     * @param string $name
     * @return int
     */
    public function getIndexByName($name)
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
    }

    /**
     * Adds a name in the list and returns an index
     * If name is exists then returns its index
     *
     * @param string $name
     * @return int
     *
     */
    public function add($name)
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }
        $this->context->getMappings();
        $index = count($this->names);
        $this->names[] = $name;
        $this->indexes[$name] = $index;
        return $index;
    }

    /**
     * Renames an item
     *
     * @param int $index
     * @param string $newName
     * @return bool
     */
    public function rename($index, $newName)
    {
        if (!isset($this->names[$index])) {
            return false;
        }
        if ($this->names[$index] === $newName) {
            return false;
        }
        $this->context->getMappings();
        $this->names[$index] = $newName;
        $this->indexes = array_flip($this->names);
        $this->onRename($index, $newName);
        return true;
    }

    /**
     * Removes an item
     *
     * @param int $index
     * @return bool
     */
    public function remove($index)
    {
        if (!isset($this->names[$index])) {
            return false;
        }
        $this->context->getMappings(); // force parsing
        unset($this->names[$index]);
        $this->names = array_values($this->names);
        $this->indexes = array_flip($this->names);
        $this->onRemove($index);
        return true;
    }

    /**
     * Fills position fields (index + name)
     *
     * @param \axy\sourcemap\PosSource $source
     * @return boolean
     * @throws \axy\sourcemap\errors\InvalidIndexed
     */
    public function fillSource($source)
    {
        $ki = $this->keyIndex;
        $kn = $this->keyName;
        $index = $source->$ki;
        $name = $source->$kn;
        if ($index !== null) {
            if (!isset($this->names[$index])) {
                $message = 'Invalid index '.$this->contextKey.'#'.$index;
                throw new InvalidIndexed($message);
            }
            $newName = $this->names[$index];
            if ($name === null) {
                $source->$kn = $newName;
            } elseif ($name !== $newName) {
                $message = 'Mismatch '.$this->contextKey.'#'.$index.' and "'.$name.'"';
                throw new InvalidIndexed($message);
            }
            return true;
        } elseif ($name !== null) {
            $source->$ki = $this->add($name);
            return true;
        }
        return false;
    }

    /**
     * Renames an item in the mappings
     *
     * @param int $index
     * @param string $newName
     * @return bool
     */
    abstract protected function onRename($index, $newName);

    /**
     * Removes an item in the mappings
     *
     * @param int $index
     * @return bool
     */
    abstract protected function onRemove($index);

    /**
     * A key from the context (contains the names list)
     * (for override)
     *
     * @var string
     */
    protected $contextKey;

    /**
     * A key from the source with index
     *
     * @var string
     */
    protected $keyIndex;

    /**
     * A key from the source with name
     *
     * @var string
     */
    protected $keyName;

    /**
     * @var string[]
     */
    protected $names;

    /**
     * @var int[]
     */
    protected $indexes;

    /**
     * @var \axy\sourcemap\parsing\Context
     */
    protected $context;
}
