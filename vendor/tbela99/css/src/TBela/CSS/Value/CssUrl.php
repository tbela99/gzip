<?php

namespace TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssUrl extends CssFunction {

    protected static function validate($data) {

        return (isset($data->name) ? $data->name : null) === 'url' && isset($data->arguments) && $data->arguments instanceof Set;
    }

    public function render(array $options = []) {

        return $this->data->name.'('. preg_replace('~^(["\'])([^\s\\1]+)\\1$~', '$2', $this->data->arguments->render($options)).')';
    }

    public function getHash() {

        return $this->data->name.'('. $this->data->arguments->getHash().')';
    }
}
