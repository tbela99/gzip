<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * CSS function value
 * @package TBela\CSS\Value
 */
class CssAttribute extends Value {

    protected static function validate($data) {

        return isset($data->arguments) && $data->arguments instanceof Set;
    }

    public function render(array $options = []) {

        return '['. $this->data->arguments->render($options).']';
    }

    public function getHash() {

        return '['. $this->data->arguments->getHash().']';
    }
}
