<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssAttribute extends Value {

    protected static function validate($data) {

        return isset($data->arguments) && is_array($data->arguments);
    }

    public function render(array $options = []) {

        return '['. $this->data->arguments->render($options).']';
    }

    public static function doRender($data, array $options = []) {

        return '['. Value::renderTokens($data->arguments, $options).']';
    }
}
