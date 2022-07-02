<?php

namespace TBela\CSS\Value;

/**
 * Css unit value
 * @package TBela\CSS\Value
 * @property-read string $unit
 */
class Unit extends Number {

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        return isset($data->unit) || (isset($data->value) && $data->value == '0') || in_array(strtolower($data->value), static::$keywords);
    }

    /**
     * @inheritDoc
     */
    public static function match($data, $type) {

        $dataType = strtolower($data->type);
        return $dataType == static::type() || ($type == 'number' && $data->value == 0);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        return static::doRender($this->data, $options);
    }

    public static function doRender($data, array $options = []) {

        if ($data->value == 0) {

            return '0'.(isset($options['omit_unit']) && isset($data->unit) && $options['omit_unit'] == false ? $data->unit : '');
        }

        $unit = !empty($options['omit_unit']) && $options['omit_unit'] == $data->unit ? '' : $data->unit;

        if (!empty($options['compress'])) {

            $value = $data->value;

            if ($data->unit == 'ms' && $value >= 100) {

                $unit = 's';
                $value /= 1000;
            }

            return static::compress($value).$unit;
        }

        return $data->value.$unit;
    }
}
