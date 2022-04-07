<?php

namespace TBela\CSS\Value;

use TBela\CSS\Interfaces\InvalidTokenInterface;
use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class InvalidCssString extends Value implements InvalidTokenInterface
{

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        return isset($data->q) && ($data->q == '"' || $data->q == "'") && isset($data->value) && !preg_match('#(?!\\\\)'.preg_quote($data->q, '#').'#', $data->value);
    }

    /**
     * @inheritDoc
     */
    public function getValue() {

        return $this->data->q.$this->data->value;
    }

    public function getHash() {

        return $this->data->q.$this->data->value;
    }

    /**
     * @inheritDoc
     * @ignore
     */
    public function render(array $options = [])
    {

        return $this->data->q.$this->data->value;
    }

    /**
     * @inheritDoc
     */
    public function recover($property = null)
    {

        return Value::getInstance((object) [
            'type' => 'css-string',
            'value' => $this->data->q.$this->value.$this->data->q
        ]);
    }
}
