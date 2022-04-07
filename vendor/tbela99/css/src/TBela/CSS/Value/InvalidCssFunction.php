<?php

namespace TBela\CSS\Value;

use TBela\CSS\Interfaces\InvalidTokenInterface;
use \TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class InvalidCssFunction extends Value implements InvalidTokenInterface {

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        return isset($data->name) && isset($data->arguments) && $data->arguments instanceof Set;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = []) {

        return $this->data->name.'('. $this->data->arguments->render($options);
    }

    /**
     * @inheritDoc
     */
    public function getValue() {

        return $this->data->arguments->{0}->value;
    }

    /**
     * @inheritDoc
     */
    public function getHash() {

        return $this->data->name.'('. $this->data->arguments->getHash();
    }

    public function recover($property = null)
    {

        $set = new Set();

        foreach ($this->arguments as $value) {

            $set->add(is_callable([$value, 'recover']) ? $value->recover() : $value);
        }

        return Value::parse($this->name.'('.$set.')', $property)->{0};
    }
}
