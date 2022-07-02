<?php

namespace TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssSrcFormat extends CssFunction {

    protected static function validate($data) {

        return (isset($data->name) ? $data->name : null) === 'format' && isset($data->arguments) && is_array($data->arguments);
    }

    public function render(array $options = []) {

        return $this->data->name.'("'. $this->data->arguments->render($options).'")';
    }
}
