<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parents;

/**
 * Partition of the SourceMap class (interfaces implementations)
 */
abstract class Interfaces extends Magic implements \IteratorAggregate, \ArrayAccess, \JsonSerializable, \Serializable
{
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getData());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $data = $this->getData();
        $data['mappings_serialized'] = $this->context->getMappings();
        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->__construct(unserialize($serialized));
    }
}
