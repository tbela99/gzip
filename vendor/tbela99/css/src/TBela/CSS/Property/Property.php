<?php

namespace TBela\CSS\Property;

use ArrayAccess;
use TBela\CSS\ArrayTrait;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Interfaces\RenderablePropertyInterface;
use TBela\CSS\Value;


/**
 * Css property
 * @package CSS
 */
class Property implements ArrayAccess, RenderableInterface, RenderablePropertyInterface
{
    use ArrayTrait, PropertyTrait;

    /**
     * @var string
     * @ignore
     */
    protected $name;

    /**
     * @var string | null
     */
    protected $vendor = null;

    protected $leadingcomments = null;

    protected $trailingcomments = null;

    /**
     * @var string
     * @ignore
     */
    protected $type = 'Property';

    /**
     * @var array
     * @ignore
     */
    protected $value;

    /**
     * Property constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * set the property value
     * @param array|string $value
     * @return Property
     */
    public function setValue($value) {

        $this->value = is_array($value) ? $value : Value::parse($value, $this->name, true, '', '', true);
        return $this;
    }

    /**
     * get the property value
     * @return array|null
     */
    public function getValue() {

        return $this->value;
    }

    /**
     * @param $vendor
     * @return Property
     */
    public function setVendor($vendor) {

        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVendor() {

        return $this->vendor;
    }

    /**
     * @param string $name
     * @return Property
     */
    public function setName($name) {

        if (substr($name, 0, 1) == '-' && preg_match('/^(-([a-zA-Z]+)-(\S+))/', trim($name), $match)) {

            $this->name = $match[3];
            $this->vendor = $match[2];
        }

        else {

            $this->name = (string) $name;
        }

        return $this;
    }

    /**
     * get the property name
     * @return string|null
     */
    public function getName($vendor = true) {

        return ($vendor && $this->vendor ? '-'.$this->vendor.'-' : '').$this->name;
    }

    /**
     * return the property type
     * @return string
     */
    public function getType() {

        return $this->type;
    }

    /**
     * convert property to string
     * @param array $options
     * @return string
     */
    public function render (array $options = []) {

        $result = ($this->vendor ? '-'.$this->vendor.'-' : null).$this->name;

        if (!empty($this->leadingcomments)) {

            $result .= ' '.implode(' ', $this->leadingcomments);
        }

        $result .= ': '.Value::renderTokens($this->value, $options);

        if (!empty($this->trailingcomments)) {

            $result .= ' '.implode(' ', $this->trailingcomments);
        }

        return $result;
    }

    /**
     * convert this object to string
     * @return string
     */
    public function __toString () {

        return $this->render();
    }

    /**
     * @inheritDoc
     */
    public function setTrailingComments(array $comments = null)
    {
        $this->trailingcomments = $comments;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTrailingComments()
    {
        return $this->trailingcomments;
    }

    /**
     * @inheritDoc
     */
    public function setLeadingComments(array $comments = null)
    {
        $this->leadingcomments = $comments;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLeadingComments()
    {
        return $this->leadingcomments;
    }

    /**
     * @inheritDoc
     */
    public function getAst()
    {
        return (object) array_filter(get_object_vars($this), function ($value) {

            return !is_null($value);
        });
    }
}