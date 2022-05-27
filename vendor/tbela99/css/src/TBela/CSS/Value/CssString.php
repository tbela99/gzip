<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class CssString extends Value
{

    /**
     * @inheritDoc
     * @ignore
     */
    protected function __construct($data)
    {
        $q = substr($data->value, 0, 1);

        if (($q == '"' || $q == "'") && strlen($data->value) > 2 && substr($data->value, -1) == $q) {

            $data->q = $q;
            $data->value = substr($data->value, 1, -1);

            if (preg_match('#^[\w_-]+$#', $data->value) && !is_numeric(\substr($data->value, 0, 1))) {

                $data->q = '';
            }

        } else if (preg_match('#^[\w_-]+$#', $data->value) && !is_numeric(\substr($data->value, 0, 1))) {

            $data->q = '';
        }

        parent::__construct($data);
    }

    /**
     * @inheritDoc
     * @ignore
     */
    public function render(array $options = [])
    {

        return static::doRender($this->data, $options);
    }

    public static function doRender($data, array $options = []) {

        $q = isset($data->q) ? $data->q : '';

        return $q.$data->value.$q;
    }
}
