<?php

namespace TBela\CSS\Property;

use ArrayAccess;
use TBela\CSS\ArrayTrait;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Value;
use TBela\CSS\Value\Set;

/**
 * Comment property class
 * @package TBela\CSS\Property
 * @method  getName()
 */
class Comment implements ArrayAccess, RenderableInterface {

    use ArrayTrait;

    /**
     * @var string|Value|Set
     * @ignore
     */
    protected $value;

    /**
     * @var string
     * @ignore
     */
    protected $type = 'Comment';

    /**
     * PropertyComment constructor.
     * @param Set | Value | string $value
     */
    public function __construct($value)
    {

        $this->setValue($value);
    }

    /**
     * Set the value
     * @param Set | Value | string $value
     * @return $this
     */
    public function setValue($value) {

        $this->value = $value;
        return $this;
    }

    /**
     * Return the object value
     * @return string
     */
    public function getValue() {

        return $this->value;
    }

    /**
     * return the object type
     * @return string
     */
    public function getType () {

        return $this->type;
    }

    /**
     * Converty this object to string
     * @param array $options
     * @return string
     */
    public function render (array $options = []) {

        if (!empty($options['remove_comments'])) {

            return '';
        }

        return $this->value;
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
     * @inheritDoc
     */
    public function setTrailingComments($comments)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTrailingComments()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setLeadingComments($comments)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLeadingComments()
    {
        return null;
    }

    /**
     * @inheritDoc
     */

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