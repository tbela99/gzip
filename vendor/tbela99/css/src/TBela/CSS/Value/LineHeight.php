<?php

namespace TBela\CSS\Value;

use \Exception;
use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class LineHeight extends Value
{
    use ValueTrait;

    protected static $keywords = [
        'normal'
    ];

    protected static $defaults = ['normal'];

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null)
    {

        if (!is_null($previousToken) && $previousToken->type != 'separator' && (!isset($previousToken->value) || $previousToken->value != '/')) {

            return false;
        }

        if (!is_null($previousValue) && $previousValue->type != 'font-size') {

            throw new Exception('invalid "font" property: "font-size" expected before line-height '.$token->value, 400);
        }

        if ($token->type == 'unit' && !in_array($token->unit, ['turn', 'rad', 'grad', 'deg'])) {

            return true;
        }

        if ($token->type == 'number') {

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
        $prefix = func_num_args() > 1 && func_get_arg(1) == 'font' ? '/ ' : '';

        $value = $this->data->value;

        if ($value == '0') {

            return '0';
        }

        if (!empty($options['compress'])) {

            if ($prefix !== '') {

                $prefix = '/';
            }

            if(is_numeric($value)) {

                $value = Number::compress($value);
            }
        }

        if (isset($this->data->unit)) {

            return $prefix.$value . $this->data->unit;
        }

        return $prefix.$value;
    }

    public function getHash() {

        if (is_null($this->hash)) {

            $this->hash = $this->render(['compress' => true]);
        }

        return $this->hash;
    }
}
