<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssUrl extends CssFunction {

    protected static function validate($data) {

        return (isset($data->name) ? $data->name : null) === 'url' && isset($data->arguments) && is_array($data->arguments);
    }

    public function render(array $options = []) {

        return $this->data->name.'('. preg_replace('~^(["\'])([^\s\\1]+)\\1$~', '$2', $this->data->arguments->render($options)).')';
    }

    /**
     * @inheritDoc
     */
    public static function doRender($data, array $options = [])
    {
        return $data->name.'('. preg_replace('~^(["\'])([^\s\\1]+)\\1$~', '$2', Value::renderTokens($data->arguments, $options)).')';
    }
}
