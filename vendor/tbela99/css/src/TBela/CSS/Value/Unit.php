<?php

namespace TBela\CSS\Value;

use phpDocumentor\Reflection\Types\Static_;

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
    public function match ($type) {

        $dataType = strtolower($this->data->type);
        return $dataType == static::type() || ($type == 'number' && $this->data->value == 0);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        if (in_array(strtolower($this->value), static::$keywords)) {

            return $this->value;
        }

        if ($this->data->value == 0) {

            return '0';
        }

        $unit = !empty($options['omit_unit']) && $options['omit_unit'] == $this->data->unit ? '' : $this->data->unit;

        if (!empty($options['compress'])) {

            $value = $this->data->value;

            if ($this->data->unit == 'ms' && $value >= 100) {

                $unit = 's';
                $value /= 1000;
            }

            return $this->compress($value).$unit;
        }

        return $this->data->value.$unit;
    }

    public function getHash() {

        if (is_null($this->hash)) {

            $this->hash = $this->render(['compress' => true]);
        }

        return $this->hash;
    }
}
