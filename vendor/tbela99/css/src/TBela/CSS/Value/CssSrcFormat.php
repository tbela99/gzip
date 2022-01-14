<?php

namespace TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssSrcFormat extends CssFunction {

    protected static function validate($data) {

        return (isset($data->name) ? $data->name : null) === 'format' && isset($data->arguments) && $data->arguments instanceof Set;
    }

    public function render(array $options = []) {

        return $this->data->name.'("'. $this->data->arguments->render($options).'")';
    }

    public function getHash() {

        return $this->data->name.'("'. $this->data->arguments->getHash().'")';
    }
}
