<?php

namespace TBela\CSS\Property;

use InvalidArgumentException;
use TBela\CSS\Value;

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
     * @param array|string $value
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

        $separator = Config::getPath('map.' . $this->shorthand . '.separator');

        $all = [];

        if (is_null($separator)) {

            $all = [$this->properties[$this->shorthand]->getValue()];
        } else {

            if (!is_array($value)) {

                $value = Value::parse($value, $name, true, '', '', true);
            }

            $index = 0;
            foreach ($this->properties[$this->shorthand]->getValue() as $v) {

                if ($v->type == 'separator' && $v->value == $separator) {

                    $index++;
                    continue;
                }

                $all[$index][] = $v;
            }

            $index = 0;
            foreach ($value as $v) {

                if ($v->type == 'separator' && $v->value == $separator) {

                    $index++;
                    continue;
                }

                $all[$index][] = $v;
            }
        }

        $props = [];
        foreach ($this->properties as $key => $prop) {

            if ($key == $this->shorthand) {

                continue;
            }

            $sep = Config::getPath('properties.' . $key . '.separator');
            $v = [];

            if (is_null($sep)) {

                $v = [$prop->getValue()];
            } else {

                $index = 0;

                foreach ($prop->getValue() as $val) {

                    if ($val->type == 'separator' && $val->value == $separator) {

                        $index++;
                        continue;
                    }

                    $v[$index][] = $val;
                }
            }


            if (count($v) != count($all)) {

                return $this;
            }

            $props[$key] = $v;
        }

        $properties = $this->property_type;
        $results = [];

        foreach ($all as $index => $values) {

            $data = [];

            foreach ($values as $val) {

                if (in_array($val->type, ['separator', 'whitespace'])) {

                    continue;
                }

                if (!isset($data[$val->type])) {

                    $data[$val->type] = $val;
                } else {

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

            foreach ($patterns as $p => $pattern) {

                foreach (preg_split('#(\s+)#', $pattern, -1, PREG_SPLIT_NO_EMPTY) as $token) {

                    if (empty($this->property_type[$token]['optional']) && (!isset($data[$token]) || (is_array($data[$token]) && !isset($data[$token][$index])))) {

                        unset($patterns[$p]);
                    }
                }
            }

            if (empty($patterns)) {

                return $this;
            }

            //
            foreach ($data as $key => $val) {

                if (!is_array($val)) {

                    $val = [$val];
                }

                $set = [];

                if (isset($properties[$key]['prefix'])) {

                    $prefix = $properties[$key]['prefix'];
                    $set[] = (object)['type' => 'separator', 'value' => is_array($prefix) ? $prefix[1] : $prefix];
                }

                $set[] = $val[0];

                //
                if (Config::getPath('map.' . $key . '.multiple')) {

                    $i = 0;
                    $j = count($val);
                    $sp = Config::getPath('map.' . $key . '.separator', ' ');

                    $sp = $sp == ' ' ? ['type' => 'whitespace'] : ['type' => 'separator', 'value' => $sp];

                    while (++$i < $j) {

                        $set[] = clone((object)$sp);
                        $set[] = $val[$i];
                    }
                }

                $data[$key] = $set;
            }

            $set = [];

            foreach (preg_split('#(\s+)#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $token) {

                if (isset($data[$token]) && isset($properties[$token]['prefix']) && is_array($properties[$token]['prefix'])) {

                    $res = $set;
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

                    $set[] = (object)['type' => 'whitespace'];
                } else if (isset($data[$token])) {

                    array_splice($set, count($set), 0, $data[$token]);
                }
            }

            $results[] = $set;
        }

        $set = [];

        $i = 0;
        $j = count($results);

        array_splice($set, count($set), 0, $results[0]);

        while (++$i < $j) {
            $set[] = (object)['type' => 'separator', 'value' => $separator];

            array_splice($set, count($set), 0, $results[$i]);
        }

        $data = Value::reduce($set, ['remove_defaults' => true]);

        if (empty($data)) {

            $this->properties[$name] = (new Property($name))->setValue($value);
            return $this;
        }

        $this->properties = [$this->shorthand => (new Property($this->shorthand))->setValue($data)->
        setLeadingComments($leadingcomments)->
        setTrailingComments($trailingcomments)];

        return $this;
    }

    /**
     * set property
     * @param string $name
     * @param string $value
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