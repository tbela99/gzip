<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class FontSize extends Value
{

    use ValueTrait;

    protected static $keywords = [

        'xx-small',
        'x-small',
        'small',
        'medium',
        'large',
        'x-large',
        'xx-large',
        'xxx-large',
        'larger',
        'smaller'
    ];

    protected static $defaults = ['medium'];

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null)
    {
        if (($token->type == 'number' && $token->value == 0) || ($token->type == 'unit' && !in_array($token->unit, ['turn', 'rad', 'grad', 'deg']))) {

            return true;
        }

        if ($token->type == 'css-string' && in_array(strtolower($token->value), static::$keywords)) {

            return true;
        }

        return $token->type == static::type();
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        $value = $this->data->value;

        if ($value == '0') {

            return '0';
        }

        if (!empty($options['compress']) && is_numeric($value)) {

            $value = Number::compress($value);
        }

        if (isset($this->data->unit)) {

            return $value . $this->data->unit;
        }

        return $value;
    }

    public function getHash() {

        if (is_null($this->hash)) {

            $this->hash = $this->render(['compress' => true]);
        }

        return $this->hash;
    }
}
