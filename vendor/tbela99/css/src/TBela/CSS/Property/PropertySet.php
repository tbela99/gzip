<?php

namespace TBela\CSS\Property;

use InvalidArgumentException;
use TBela\CSS\Value;
use TBela\CSS\Value\Set;

/**
 * Compute shorthand properties. Used internally by PropertyList to compute shorthand for properties of the same type. example margin, margin-left, margin-top, margin-right, margin-bottom
 * @package TBela\CSS\Property
 */
class PropertySet
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

        foreach ($config['properties'] as $property) {

            $config[$property] = Config::getProperty($property);
            $this->property_type[$config[$property]['type']][] = $property;

            unset($config[$property]['shorthand']);
        }

        $this->config = $config;

        if (isset($config['pattern']) && is_array($config['pattern'])) {

            $this->config['pattern'] = $config['pattern'][0];
        }
    }

    /**
     * set property value
     * @param string $name
     * @param Set $value
     * @param array|null $leadingcomments
     * @param array|null $trailingcomments
     * @return PropertySet
     */
    public function set($name, $value, array $leadingcomments = null, array $trailingcomments = null, $vendor = null)
    {

        $propertyName = ($vendor ? '-'.$vendor.'-' : '').$name;

        // is valid property
        if (($this->shorthand != $propertyName) && !in_array($propertyName, $this->config['properties'])) {

            throw new InvalidArgumentException('Invalid property ' . $propertyName.' : '.$this->shorthand, 400);
        }

        // $name is shorthand -> expand
        if ($this->shorthand == $propertyName) {

            foreach ($this->config['properties'] as $property) {

                unset($this->properties[$property]);
            }

            if (is_string($value)) {

                $value = Value::parse($value, $name);
            }

            $result = $this->expand($value);

            if (is_array($result)) {

                $this->expandProperties($result, $leadingcomments, $trailingcomments, $vendor);
                unset($this->properties[$this->shorthand]);
            } else {

                $this->setProperty($propertyName, $value);

                if (!is_null($leadingcomments)) {

                    $this->properties[$propertyName]->setLeadingComments($leadingcomments);
                }

                if (!is_null($trailingcomments)) {

                    $this->properties[$propertyName]->setTrailingComments($trailingcomments);
                }
            }

        } else {

            if (isset($this->properties[$this->shorthand])) {

                $shorthandValue = $this->properties[$this->shorthand]->getValue();

                $result = $this->expand($shorthandValue);

                if ($result !== false) {

                    $this->expandProperties($result);
                } else {

                    foreach ($this->config['properties'] as $property) {

                        $this->properties[$property] = (new Property($property))->setValue(clone $shorthandValue);
                    }
                }

                unset($this->properties[$this->shorthand]);
            }

            $this->setProperty($propertyName, $value);

            if (!is_null($leadingcomments)) {

                $this->properties[$propertyName]->setLeadingComments($leadingcomments);
            }

            if (!is_null($trailingcomments)) {

                $this->properties[$propertyName]->setTrailingComments($trailingcomments);
            }
        }

        return $this;
    }

    protected function expandProperties(array $result, array $leadingcomments = null, array $trailingcomments = null, $vendor = null)
    {

        foreach ($result as $property => $values) {

            $separator = Config::getProperty($property . '.separator', ' ');

            if ($separator != ' ') {

                $separator = ' ' . $separator . ' ';
            }

            $this->setProperty($property, implode($separator, $values), $vendor);

            if (!is_null($leadingcomments)) {

                $this->properties[$property]->setLeadingComments($leadingcomments);
            }

            if (!is_null($trailingcomments)) {

                $this->properties[$property]->setTrailingComments($trailingcomments);
            }
        }

        return $this;
    }

    /**
     * expand shorthand property
     * @param Set $value
     * @return array|bool
     * @ignore
     */
    protected function expand($value)
    {

        if (is_string($value)) {

            $value = Value::parse($value, $this->shorthand);
        }

        $pattern = explode(' ', $this->config['pattern']);
        $value_map = [];
        $values = [];
        $result = [];

        $separator = isset($this->config['separator']) ? $this->config['separator'] : null;
        $index = 0;

        foreach ($value as $v) {

            if ($v->value == $separator) {

                $index++;
            } else {

                $value_map[$index][] = $v;
            }
        }

        $vmap = $value_map;

        foreach ($pattern as $key => $match) {

            foreach ($value_map as $index => $map) {

                foreach ($map as $i => $v) {

                    if ($v->type == 'whitespace') {

                        unset($vmap[$index][$i]);
                    } else if ($v->match($match)) {

                        $values[$index][$match][] = $v;
                        unset($vmap[$index][$i]);
                        break;
                    }
                }

                if (empty($vmap[$index])) {

                    unset($value_map[$index]);
                } else {

                    $value_map[$index] = $vmap[$index];
                }
            }
        }

        // does not match the pattern
        if (!empty($value_map)) {

            return false;
        }

        foreach ($value_map as $val) {

            foreach ($val as $v) {

                if ($v->type != 'whitespace' && $v->type != 'separator') {

                    // failure to match the pattern
                    return false;
                }
            }
        }

        foreach ($values as $types) {

            foreach ($this->property_type as $unit => $properties) {

                foreach ($properties as $property) {

                    // value not set
                    if (!isset($types[$unit])) {

                        continue;
                    }

                    $index = array_search($property, $this->property_type[$unit], true);
                    $key = null;

                    $list = $types[$unit];

                    if (isset($list[$index])) {

                        $key = $index;
                    } else {

                        if (isset($this->config['value_map'][$property])) {

                            foreach ($this->config['value_map'][$property] as $item) {

                                if (isset($list[$item])) {

                                    $key = $item;
                                    break;
                                }
                            }
                        }
                    }

                    if (!is_null($key)) {

                        $result[$property][] = $list[$key];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * convert 'border-radius: 10% 17% 10% 17% / 50% 20% 50% 20% -> 'border-radius: 10% 17% / 50% 20%
     * @return string
     * @ignore
     */
    protected function reduce()
    {
        $result = [];

        foreach ($this->property_type as $unit => $properties) {

            foreach ($properties as $property) {

                if (isset($this->properties[$property])) {

                    $prop = $this->properties[$property];
                    $type = $this->config[$property]['type'];
                    $separator = isset($this->config[$property]['separator']) ? $this->config[$property]['separator'] : ' ';
                    $index = 0;

                    foreach ($prop['value'] as $v) {

                        if ($v->type != 'whitespace' && $v->type != 'separator' && !$v->match($type)) {

                            $result = [];
                            break 3;
                        }

                        if (!is_null($separator) && $v->value == $separator) {

                            $index++;
                        } else {

                            $result[$index][$property] = [$type, $v];
                        }
                    }
                }
            }
        }

        if (isset($this->config['value_map'])) {

            foreach ($result as $index => $values) {

                foreach ($this->config['value_map'] as $property => $set) {

                    $prop = $this->config['properties'][$set[0]];

                    if (isset($values[$property][1]) && isset($values[$prop][1]) && (string)$values[$property][1] == (string)$values[$prop][1]) {

                        unset($values[$property]);
                        continue;
                    }

                    break;
                }

                $result[$index] = trim(preg_replace_callback('#\S+#', function ($matches) use (&$values) {

                    foreach ($values as $key => $property) {

                        if ($property[0] == $matches[0]) {

                            unset($values[$key]);

                            return $property[1];
                        }
                    }

                    return '';

                }, $this->config['pattern']));
            }
        }

        $separator = Config::getProperty($this->config['shorthand'] . '.separator', ' ');

        if ($separator != ' ') {

            $separator = ' ' . $separator . ' ';
        }

        return implode($separator, $result);
    }

    /**
     * set property
     * @param string $name
     * @param string $value
     * @param null $vendor
     * @return PropertySet
     * @ignore
     */
    protected function setProperty($name, $value, $vendor = null)
    {

        $propertyName = ($vendor ? '-'.$vendor.'-' : '').$name;

        if (!isset($this->properties[$propertyName])) {

            $this->properties[$propertyName] = new Property($name);
        }

        if ($vendor) {

            $this->properties[$propertyName]->setVendor($vendor);
        }

        $this->properties[$propertyName]->setValue($value);

        return $this;
    }

    /**
     * return Property array
     * @return Property[]
     */
    public function getProperties()
    {

        // match pattern
        if (count($this->properties) == count($this->config['properties'])) {

            $value = $this->reduce();

            if ($value !== false && $value !== '') {

                return [(new Property($this->config['shorthand']))->setValue($value)];
            }

            // does not match pattern, still check for identical values
            $canReduce = true;
            $hash = '';
            $value = null;

            foreach ($this->properties as $property) {

                if ($hash === '') {

                    $value = $property->getValue();
                    $hash = $value->getHash();
                    continue;
                }

                else if ($hash !== $property->getValue()->getHash()) {

                    $canReduce = false;
                    break;
                }
            }

            if ($canReduce) {

                return [(new Property($this->config['shorthand']))->setValue($value)];
            }
        }

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

        // should use shorthand?
        if (count($this->properties) == count($this->config['properties'])) {

            $value = $this->reduce();

            if ($value !== false) {

                return $this->config['shorthand'] . ': ' . $value;
            }
        }

        foreach ($this->properties as $property) {

            $value .= $property->render() . $glue . $join;
        }

        return rtrim($value, $glue . $join);
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