<?php

namespace TBela\CSS\Parser;

trait AccessTrait
{

    public function __get($name) {

        if (method_exists($this, 'get'.$name)) {

            return $this->{'get'.$name}();
        }
    }

    public function __set($name, $value) {

        if (method_exists($this, 'set'.$name)) {

            return $this->{'set'.$name}($value);
        }
    }


    public function __isset($key) {

        return is_callable([$this, 'get'.$key]) && isset($this->{$key});
    }

    public function __clone() {

        foreach ($this as $key => $value) {

            if (is_object($value)) {

                $this->{$key} = clone $value;
            }

            if (is_array($value)) {

                array_walk($this->{$key}, function ($value, $index) use($key) {

                    if (is_object($value)) {

                        $this->{$key}[$index] = clone $value;
                    }
                });
            }
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}