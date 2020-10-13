<?php

namespace TBela\CSS\Property;

use ArrayAccess;
use TBela\CSS\ArrayTrait;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Interfaces\RenderablePropertyInterface;
use TBela\CSS\Value;
use TBela\CSS\Value\Set;


/**
 * Css property
 * @package CSS
 */
class Property implements ArrayAccess, RenderableInterface, RenderablePropertyInterface
{
    use ArrayTrait;

    /**
     * @var string
     * @ignore
     */
    protected $name;

    protected $leadingcomments = null;

    protected $trailingcomments = null;
    /**
     * @var string
     * @ignore
     */
    protected $type = 'Property';

    protected $value;

    /**
     * Property constructor.
     * @param Value\Set|string$name
     */
    public function __construct($name)
    {

        $this->name = (string) $name;
    }

    /**
     * set the property value
     * @param Set|string $value
     * @return $this
     */
    public function setValue($value) {

        $this->value = !($value instanceof Set) ? Value::parse($value, $this->name) : $value;
        return $this;
    }

    /**
     * get the property value
     * @return Set|null
     */
    public function getValue() {

        if (!($this->value instanceof Set)) {

            $this->value = Value::parse($this->value, $this->name);
        }

        return $this->value;
    }

    /**
     * get the property name
     * @return string
     */
    public function getName() {

        return $this->name;
    }

    /**
     * return the property type
     * @return string
     */
    public function getType() {

        return $this->type;
    }

    /**
     * get property hash.
     * @return string
     */
    public function getHash() {

        return $this->name.':'.$this->value->getHash();
    }

    /**
     * convert property to string
     * @param array $options
     * @return string
     */
    public function render (array $options = []) {

        $result = $this->name;

        if (!empty($this->leadingcomments)) {

            $result .= ' '.implode(' ', $this->leadingcomments);
        }

        $result .= ': '.$this->value->render($options);

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
    public function setTrailingComments($comments)
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
    public function setLeadingComments($comments)
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
}