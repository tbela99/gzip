<?php

namespace TBela\CSS\Property;

use InvalidArgumentException;
use TBela\CSS\Value;
use TBela\CSS\Value\Set;

/**
 * Compute shorthand properties. Used internally by PropertyList to compute shorthand for properties of different types
 * @package TBela\CSS\Property
 */
class PropertyMap
{

    use PropertyTrait;

    /**
     * @var array
     * @ignore
     */
    protected $config;

    /**
     * @var Property[]
     * @ignore
     */
    protected $properties = [];

    /**
     * @var array
     * @ignore
     */
    protected $property_type = [];
    /**
     * @var string
     * @ignore
     */
    protected $shorthand;

    /**
     * PropertySet constructor.
     * @param string $shorthand
     * @param array $config
     */
    public function __construct($shorthand, array $config)
    {

        $this->shorthand = $shorthand;

        $config['required'] = [];

        if (isset($config['properties'])) {

            foreach ($config['properties'] as $property) {

                $config[$property] = Config::getPath('map.' . $property);

                unset($config[$property]['shorthand']);

                $this->property_type[$property] = $config[$property];

                if (empty($config[$property]['optional'])) {

                    $config['required'][] = $property;
                }
            }
        }

        $this->config = $config;
    }

    /**
     * set property value
     * @param string $name
     * @param Set|string $value
     * @param array|null $leadingcomments
     * @param array|null $trailingcomments
     * @return PropertyMap
     */
    public function set($name, $value, array $leadingcomments = null, array $trailingcomments = null)
    {

        // is valid property
        if (($this->shorthand != $name) && !in_array($name, $this->config['properties'])) {

            throw new InvalidArgumentException('Invalid property ' . $name, 400);
        }

        if (!($value instanceof Set)) {

            $value = Value::parse($value, $name);
        }

        // the type matches the shorthand - example system font
        if ($name == $this->shorthand || !isset($this->properties[$this->shorthand])) {

            if ($name == $this->shorthand) {

                $this->properties = [];
            }

            if (!isset($this->properties[$name])) {

                $this->properties[$name] = new Property($name);
            }

            $this->properties[$name]->setValue($value)->
                                        setLeadingComments($leadingcomments)->
                                        setTrailingComments($trailingcomments);

            return $this;
        }

        $this->properties[$name] = (new Property($name))->setValue($value)->
                                                setLeadingComments($leadingcomments)->
                                                setTrailingComments($trailingcomments);

        $separator = Config::getPath('map.'.$this->shorthand.'.separator');

        $all = is_null($separator) ? [$this->properties[$this->shorthand]->getValue()] : $this->properties[$this->shorthand]->getValue()->split($separator);

        $props = [];

        foreach ($this->properties as $key => $prop) {

            if ($key == $this->shorthand) {

                continue;
            }

            $sep = Config::getPath('properties.'.$key.'.separator');

            $v = is_null($sep) ? [$prop->getValue()] : $prop->getValue()->split($sep);

            if (count($v) != count($all)) {

                return $this;
            }

            $props[$key] = array_map(function ($v) { return $v->toArray(); }, $v);
        }

        $properties = $this->property_type;
        $results = [];

        foreach($all as $index => $values) {

            $data = [];

            foreach ($values as $val) {

                if (in_array($val->type, ['separator', 'whitespace'])) {

                    continue;
                }

                if (!isset($data[$val->type])) {

                    $data[$val->type] = $val;
                }
                else {

                    if (!is_array($data[$val->type])) {

                        $data[$val->type] = [$data[$val->type]];
                    }

                    $data[$val->type][] = $val;
                }
            }

            foreach ($props as $k => $prop) {

                if ($name == $this->shorthand) {

                    continue;
                }

                $data[$k] = $prop[$index];
            }

            // match
            $patterns = $this->config['pattern'];

            foreach ($patterns as $name => $pattern) {

                foreach (preg_split('#(\s+)#', $pattern, -1, PREG_SPLIT_NO_EMPTY) as $token) {

                    if (empty($this->property_type[$token]['optional']) && (!isset($data[$token]) || (is_array($data[$token]) && !isset($data[$token][$index])))) {

                        unset($patterns[$name]);
                    }
                }
            }

            if (empty($patterns)) {

                return $this;
            }

            //
            foreach ($data as $key => $value) {

                if (!is_array($value)) {

                    $value = [$value];
                }

                $set = new Set;

                if (isset($properties[$key]['prefix'])) {

                    $prefix = $properties[$key]['prefix'];
                    $set->add(Value::getInstance((object)['type' => 'separator', 'value' => is_array($prefix) ? $prefix[1] : $prefix]));
                }

                $set->add($value[0]);

                //
                if (Config::getPath('map.'.$key.'.multiple')) {

                    $i = 0;
                    $j = count($value);
                    $sp = Config::getPath('map.'.$key.'.separator', ' ');

                    $sp = $sp == ' ' ? ['type' => 'whitespace'] : ['type' => 'separator', 'value' => $sp];

                    while (++$i < $j) {

                        $set->add(Value::getInstance((object) $sp));
                        $set->add($value[$i]);
                    }
                }

                $data[$key] = $set;
            }

            $set = new Set;

            foreach(preg_split('#(\s+)#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $token) {

                if (isset($data[$token]) && isset($properties[$token]['prefix']) && is_array($properties[$token]['prefix'])) {

                    $res = $set->toArray();

                    $j = count($res);

                    while ($j--) {

                        if (in_array($res[$j]->type, ['whitespace', 'separator'])) {

                            continue;
                        }

                        if ((isset($properties[$token]['multiple']) && $res[$j]->type == $token) ||
                            $res[$j]->type == $properties[$token]['prefix'][0]['type']) {

                            break;
                        }

                        if ($res[$j]->type != $properties[$token]['prefix'][0]['type']) {

                            return $this;
                        }
                    }
                }

                if (trim($token) == '') {

                    $set->add(Value::getInstance((object) ['type' => 'whitespace']));
                }

                else if (isset($data[$token])) {

                    $set->merge($data[$token]);
                }
            }

            $results[] = $set;
        }

        $set = new Set;

        $i = 0;
        $j = count($results);

        $set->merge($results[0]);

        while (++$i < $j) {

            $set->add(Value::getInstance((object) ['type' => 'separator', 'value' => $separator]));
            $set->merge($results[$i]);
        }

        $data = Value::reduce($set->toArray(), ['remove_defaults' => true]);

        $this->properties = [$this->shorthand => (new Property($this->shorthand))->setValue(new Set($data))->
        setLeadingComments($leadingcomments)->
        setTrailingComments($trailingcomments)];

        return $this;
    }

    /**
     * set property
     * @param string $name
     * @param Value\Set|string $value
     * @return PropertyMap
     * @ignore
     */
    protected function setProperty($name, $value)
    {

        if (!isset($this->properties[$name])) {

            $this->properties[$name] = new Property($name);
        }

        $this->properties[$name]->setValue($value);

        return $this;
    }

    /**
     * return Property array
     * @return Property[]
     */
    public function getProperties()
    {

        return array_values($this->properties);
    }

    /**
     * convert this object to string
     * @param string $join
     * @return string
     */
    public function render($join = "\n")
    {
        $glue = ';';
        $value = '';

        foreach ($this->properties as $property) {

            $value .= $property->render() . $glue . $join;
        }

        return rtrim($value, $glue . $join);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {

        return !empty($this->properties);
    }

    /**
     * convert this object to string
     * @return string
     */
    public function __toString()
    {

        return $this->render();
    }
}