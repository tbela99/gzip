<?php

namespace TBela\CSS\Value;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use TBela\CSS\Interfaces\ObjectInterface;
use TBela\CSS\Value;

/**
 * string tokens set
 * @package CSS
 */
class Set implements IteratorAggregate, JsonSerializable, Countable, ObjectInterface
{
    /**
     * @var Value[]
     * @ignore
     */
    protected $data = [];

    /**
     * Set constructor.
     * @param Value[] $data
     */
    public function __construct(array $data = [])
    {

        $this->data = array_map([Value::class, 'getInstance'], $data);
    }

    /**
     * @param string $name
     * @return mixed|null
     * @ignore
     */
    public function __get($name)
    {
        if(isset($this->data[$name])) {

            return $this->data[$name];
        }

        return null;
    }

    public function getHash() {

        return implode(',', array_map(function (Value $value) {

            return $value->getHash();

        }, $this->data));
    }

    /**
     * Convert this object to string
     * @param array $options
     * @return string
     */
    public function render (array $options = []) {

        $result = '';
        $join = ','.(!empty($options['compress']) ? '' : ' ');

        foreach ($this->doSplit($this->data, ',') as $data) {

            foreach($data as $item) {

                $result .= $item->render($options);
            }

            $result .= $join;
        }

        return rtrim($result, $join);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function match(string $type)
    {
        foreach ($this->data as $value) {

            if (in_array($value->type, ['separator', 'whitespace'])) {

                continue;
            }

            if (!$value->match($type)) {

                return false;
            }
        }

        return true;
    }

    /**
     * filter values
     * @param callable $filter
     * @return $this
     */
    public function filter (callable $filter) {

        $this->data = array_filter($this->data, $filter);
        return $this;
    }

    /**
     * map values
     * @param callable $map
     * @return $this
     */
    public function map (callable $map) {

        $this->data = array_map($map, $this->data);
        return $this;
    }

    /**
     * append the second set data to the first set data
     * @param Set[] $sets
     * @return Set
     */
    public function merge (Set ...$sets) {

        foreach ($sets as $set) {

            array_splice($this->data, count($this->data), 0, $set->data);
        }

        return $this;
    }

    /**
     * split a set according to $separator
     * @param string $separator
     * @return Set[]
     */
    public function split ($separator) {

        return $this->doSplit($this->data, $separator);
    }

    /**
     * @param array $data
     * @param string $separator
     * @return Set[]
     * @ignore
     */
    protected function doSplit (array $data, $separator) {

        if (empty($data)) {

            return [];
        }

        $values = [];

        $current = new Set;

        foreach ($data as $value) {

            if ($value->value === $separator) {

                $values[] = $current;
                $current = new Set;
            }

           else {

                $current->data[] = clone $value;
            }
        }

        if (end($values) !== $current) {

            $values[] = $current;
        }

        return $values;
    }

    /**
     * add an item to the set
     * @param Value $value
     * @return $this
     */
    public function add(Value $value) {

        $this->data[] = $value;
        return $this;
    }

    /**
     * Automatically convert this object to string
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * return an array of internal data
     * @return Value[]
     */
    public function toArray() {

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function toObject()
    {
        $result = [];

        foreach ($this->data as $datum) {

            $result[] = $datum->toObject();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return (string) $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }
}