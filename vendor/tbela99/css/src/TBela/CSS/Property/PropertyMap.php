<?php

namespace TBela\CSS\Property;

use InvalidArgumentException;
use TBela\CSS\Value;
use TBela\CSS\Value\Font;
use TBela\CSS\Value\Set;

/**
 * Compute shorthand properties. Used internally by PropertyList to compute shorthand for properties of different types
 * @package TBela\CSS\Property
 */
class PropertyMap
{

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

        foreach ($config['properties'] as $property) {

            $config[$property] = Config::getPath('map.' . $property);

            unset($config[$property]['shorthand']);

            $this->property_type[$property] = $config[$property];

            if (empty($config[$property]['optional'])) {

                $config['required'][] = $property;
            }
        }

        $this->config = $config;
    }

    /**
     * set property value
     * @param string $name
     * @param Set $value
     * @return PropertyMap
     */
    public function set($name, $value, $leadingcomments = null, $trailingcomments = null)
    {

        $property = $name instanceof Set ? trim($name->render(['remove_comments' => true])) : $name;

        // is valid property
        if (($this->shorthand != $property) && !in_array($property, $this->config['properties'])) {

            throw new InvalidArgumentException('Invalid property ' . $name, 400);
        }

        if (is_string($value)) {

            $value = Value::parse($value, $name);
        }

        if (isset($this->properties[$this->shorthand]) || $name == $this->shorthand) {

            // the type matches the shorthand - example system font
            if ($property != $this->shorthand) {

                try {

                    // can we parse this shorthand?
                    Font::matchPattern($this->properties[$this->shorthand]->getValue()->toArray());
                }

                catch (\Exception $e) {

                    // no? append the new property
                    $this->properties[$name] = (new Property($name))->setValue($value);
                    return $this;
                }

                foreach ($this->properties[$this->shorthand]->getValue() as $val) {

                    if ($val->type == $this->shorthand) {

                        if (!isset($this->properties[$property])) {

                            $this->properties[$property] = new Property($name);
                        }

                        $this->properties[$name]->setValue($value);

                        if (!is_null($leadingcomments)) {

                            $this->properties[$name]->setLeadingComments($leadingcomments);
                        }

                        if (!is_null($trailingcomments)) {

                            $this->properties[$name]->setTrailingComments($trailingcomments);
                        }

                        return $this;
                    }
                }
            }

            $this->properties = isset($this->properties[$this->shorthand]) ? [$this->shorthand => $this->properties[$this->shorthand]] : [];

            if (!isset($this->properties[$this->shorthand])) {

                $this->properties[$this->shorthand] = new Property($this->shorthand);
            }

            if ($name == $this->shorthand) {

                $this->properties[$this->shorthand]->setValue($value);

                if (!is_null($leadingcomments)) {

                    $this->properties[$this->shorthand]->setLeadingComments($leadingcomments);
                }

                if (!is_null($trailingcomments)) {

                    $this->properties[$this->shorthand]->setTrailingComments($trailingcomments);
                }

                return $this;
            }
        } else {

            if (!isset($this->properties[$property])) {

                $this->properties[$property] = new Property($name);
            }

            $this->properties[$property]->setValue($value);

            if (!is_null($leadingcomments)) {

                $this->properties[$name]->setLeadingComments($leadingcomments);
            }

            if (!is_null($trailingcomments)) {

                $this->properties[$name]->setTrailingComments($trailingcomments);
            }

            if (!empty($this->config['settings']['compute'])) {

                return $this->computeProperties();
            }

            return $this;
        }

        $properties = $this->property_type;
        $values = array_merge([], $properties);

        // create a map of existing values
        foreach ($this->properties[$this->shorthand]->getValue() as $val) {

            if (isset($properties[$val->type])) {

                // allow multiple values - example font family
                if (!empty($properties[$val->type]['multiple'])) {

                    $properties[$val->type]['value'][] = new Set([$val]);
                } else {

                    $properties[$val->type]['value'] = new Set([$val]);
                }
            }
        }

        if (!is_object($value)) {

            $value = Value::parse($value, $name);
        }

        foreach ($value as $val) {

            if (isset($properties[$val->type])) {

                if (!empty($properties[$val->type]['multiple'])) {

                    $values[$val->type]['value'][] = new Set([$val]);
                } else {

                    $values[$val->type]['value'] = new Set([$val]);
                }
            }
        }

        foreach ($values as $key => $val) {

            if (!isset($values[$key]['value'])) {

                unset($values[$key]);
            }
        }

        $properties = array_merge($properties, $values);

        foreach ($properties as $key => $property) {

            if (!isset($property['value'])) {

                continue;
            }

            if (is_array($property['value'])) {

                $data = ['type' => 'whitespace'];

                if (isset($property['separator'])) {

                    $data = ['type' => 'separator', 'value' => $property['separator']];
                }

                $val = new Set;
                $j = count($property['value']);

                for ($i = 0; $i < $j; $i++) {

                    $val->merge($property['value'][$i]);

                    if ($i < $j - 1) {

                        $val->add(Value::getInstance((object)$data));
                    }
                }

                $properties[$key]['value'] = $val;
            }
        }

        $set = new Set;

        // compute the shorthand and render?
        foreach ($properties as $key => $prop) {

            if (!isset($prop['value'])) {

                continue;
            }

            if (isset($prop['prefix'])) {

                $set->add(Value::getInstance((object)['type' => 'separator', 'value' => $prop['prefix']]));
            }

            $set->merge($prop['value']);
            $set->add(Value::getInstance((object)['type' => 'whitespace']));
        }

        $data = $set->toArray();

        if (count($properties) > 1) {

            array_pop($data);
        }

        $this->properties[$this->shorthand]->setValue(new Set(Value::reduce($data, ['remove_defaults' => true])));

        if (!is_null($leadingcomments)) {

            $this->properties[$this->shorthand]->setLeadingComments($leadingcomments);
        }

        if (!is_null($trailingcomments)) {

            $this->properties[$this->shorthand]->setTrailingComments($trailingcomments);
        }

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
     * compute shorthand property
     * @return $this
     */
    protected function computeProperties()
    {

        foreach ($this->config['pattern'] as $pattern) {

            $values = [];
            foreach (preg_split('#(\s+)#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $token) {

                if (trim($token) === '') {

                    $values[] = Value::getInstance((object) ['type' => 'whitespace']);
                }

                else {

                    if (!isset($this->properties[$token])) {

                        continue 2;
                    }

                    array_splice($values, count($values), 0, $this->properties[$token]->getValue()->toArray());
                }
            }

            $property = new Property($this->shorthand);
            $property->setValue(new Set($values));

            $this->properties = [$this->shorthand => $property];
            break;
        }

        return $this;
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