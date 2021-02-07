<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * Css number value
 * @package TBela\CSS\Value
 */
class Number extends Value
{
    /**
     * @inheritDoc
     */
    protected function __construct($data)
    {
        parent::__construct($data);

        if (strpos($this->data->value, 'e') !== false) {

            $value = (float) $this->data->value;

            if ($value == intval($value)) {

                $value = (int) $value;
            }

            $this->data->value = (string) $value;
        }
    }

    public function getHash() {

        return $this->data->value;
    }

    /**
     * @inheritDoc
     */
    public function match ($type) {

        return ($this->data->value == '0' && $type == 'unit') || $this->data->type == $type;
    }

    /**
     * @inheritDoc
     */
    protected static function validate($data)
    {

        return isset($data->value) && is_numeric($data->value) && $data->value !== '';
    }

    /**
     * @param string $value
     * @return string
     * @ignore
     */
    public static function compress($value)
    {

        if (is_null($value)) {

            return '';
        }

        $value = explode('.', (float) $value);

        if (isset($value[1]) && $value[1] == 0) {

            unset($value[1]);
        }

        if (isset($value[1])) {

            // convert 0.20 to .2
            $value[1] = rtrim($value[1], '0');

            if ($value[0] == 0) {

                $value[0] = '';
            }

        } else {

            // convert 1000 to 1e3
            $value[0] = preg_replace_callback('#(0{3,})$#', function ($matches) {

                return 'e' . strlen($matches[1]);
            }, $value[0]);
        }

        return implode('.', $value);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        if (!empty($options['compress'])) {

            return $this->compress($this->data->value);
        }

        return $this->data->value;
    }
}
