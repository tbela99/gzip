<?php

namespace TBela\CSS\Property;

use InvalidArgumentException;
use TBela\CSS\Value;

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
     * @param array|string $value
     * @param array|null $leadingcomments
     * @param array|null $trailingcomments
     * @param null $vendor
     * @return PropertySet
     */
    public function set($name, $value, array $leadingcomments = null, array $trailingcomments = null, $vendor = null)
    {

        $propertyName = ($vendor ? '-' . $vendor . '-' : '') . $name;

        // is valid property
        if (($this->shorthand != $propertyName) && !in_array($propertyName, $this->config['properties'])) {

            throw new InvalidArgumentException('Invalid property ' . $propertyName . ' : ' . $this->shorthand, 400);
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

            if ($result === false) {

                $this->setProperty($name, $value, $vendor);
                return $this;
            }

            if (is_array($result)) {

                $this->expandProperties($result, $leadingcomments, $trailingcomments, $vendor);
                unset($this->properties[$this->shorthand]);
            } else {

                $this->setProperty($name, $value, $vendor);

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

                        $this->properties[$property] = (new Property($property))->setValue($shorthandValue);
                    }
                }

                unset($this->properties[$this->shorthand]);
            }

            $this->setProperty($name, $value, $vendor);

            if (!is_null($leadingcomments)) {

                $this->properties[$propertyName]->setLeadingComments($leadingcomments);
            }

            if (!is_null($trailingcomments)) {

                $this->properties[$propertyName]->setTrailingComments($trailingcomments);
            }
        }

        return $this;
    }

    /**
     * @param array $result
     * @param array|null $leadingcomments
     * @param array|null $trailingcomments
     * @param string|null $vendor
     * @return $this
     */
    protected function expandProperties(array $result, array $leadingcomments = null, array $trailingcomments = null, $vendor = null)
    {

        foreach ($result as $property => $values) {

            $this->setProperty($property, $values, $vendor);

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
     * @param array|string $value
     * @return array|bool
     * @ignore
     */
    protected function expand($value)
    {

        if (is_string($value)) {

            $value = Value::parse($value, $this->shorthand, true, '', '', true);
        }

        $pattern = explode(' ', $this->config['pattern']);
        $value_map = [];
        $values = [];
        $result = [];

        $separator = isset($this->config['separator']) ? $this->config['separator'] : null;
        $index = 0;

        foreach ($value as $v) {

            if ($v->type == 'css-string' && $v->value == '!important') {

                return false;
            }

            if (isset($v->value) && $v->value == $separator) {

                $index++;
            } else {

                $value_map[$index][] = $v;
            }
        }

        $vmap = $value_map;

        foreach ($pattern as $match) {

            foreach ($value_map as $index => $map) {

                foreach ($map as $i => $v) {

                    $className = Value::getClassName($v->type);

                    if ($v->type == 'whitespace') {

                        unset($vmap[$index][$i]);
                    } else if ($className::match($v, $match)) {

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

        foreach ($result as $property => $values) {

            $i = count($values);

            if ($i > 0) {

                $separator = isset($this->config[$property]['separator']) ? $this->config[$property]['separator'] : ' ';

                $separator = (object)($separator == ' ' ? [

                    'type' => 'whitespace'
                ] :
                    [
                        'type' => 'separator',
                        'value' => $separator
                    ]);

                while ($i-- > 1) {

                    array_splice($result[$property], $i, 0, [clone $separator]);
                }
            }
        }

        return $result;
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

        $propertyName = ($vendor && substr($name, 0, strlen($vendor) + 2) != '-' . $vendor . '-' ? '-' . $vendor . '-' : '') . $name;

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
     * @throws \Exception
     */
    public function getProperties()
    {

        $properties = $this->properties;

        foreach ($properties as $property) {

            foreach ($property->getValue() as $value) {

                if ($value->type == 'css-string' && $value->value == '!important') {

                    return array_values($properties);
                }
            }
        }

        if (isset($this->config['value_map']) && count($this->config['properties']) == count($properties)) {

            $all = [];
            $keys = array_keys($properties);

            foreach ($keys as $property) {

                $all[$property] = Value::splitValues($properties[$property]->getValue(), $property);
            }

            $count = count(current($all));

            foreach ($all as $item) {

                if (count($item) != $count) {

                    return array_values($properties);
                }
            }

            $result = [];
            $separator = Config::getProperty($this->config['shorthand'] . '.separator', ' ');

            for ($i = 0; $i < $count; $i++) {

                $values = [];

                foreach ($keys as $key) {

                    $values[$key] = $all[$key][$i];
                }

                foreach ($this->config['value_map'] as $property => $set) {

                    $prop = $this->config['properties'][$set[0]];

                    if (!isset($properties[$property]) || !isset($properties[$prop])) {

                        break;
                    }

                    $v1 = $values[$property];
                    $v2 = $values[$prop];

                    if (count($v1) == count($v2) && Value::equals($v1, $v2)) {

                        unset($values[$property]);
                        continue;
                    }

                    break;
                }

                $result[$i] = $values;
            }

            $token = (object)['type' => $separator == ' ' ? 'whitespace' : 'separator'];

            if ($token->type == 'separator') {

                $token->value = $separator;
            }

            $values = [];

            $v = [];
            foreach ($result[0] as $val) {

                array_splice($v, count($v), 0, $val);
            }

            $l = count($v);

            while ($l-- > 1) {

                array_splice($v, $l, 0, [(object)['type' => 'whitespace']]);
            }

            array_splice($values, count($values), 0, $v);

            $j = count($result);

            for ($i = 1; $i < $j; $i++) {

                $values[] = clone $token;

                $v = [];
                foreach ($result[$i] as $val) {

                    array_splice($v, count($v), 0, $val);
                }

                $l = count($v);

                while ($l-- > 1) {

                    array_splice($v, $l, 0, [(object)['type' => 'whitespace']]);
                }

                array_splice($values, count($values), 0, $v);
            }

            return [(new Property($this->shorthand))->setValue($values)];
        }

        return array_values($properties);
    }

    /**
     * convert this object to string
     * @param string $join
     * @return string
     * @throws \Exception
     */
    public function render($join = "\n")
    {
        $glue = ';';
        $value = '';

        foreach ($this->getProperties() as $property) {

            $value .= $property->render() . $glue . $join;
        }

        return rtrim($value, $glue . $join);
    }

    /**
     * convert this object to string
     * @return string
     * @throws \Exception
     */
    public function __toString()
    {

        return $this->render();
    }
}