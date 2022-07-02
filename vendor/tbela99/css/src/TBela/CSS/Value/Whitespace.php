<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css whitespace value
 * @package TBela\CSS\Value
 */
class Whitespace extends Value {

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getValue () {

        return ' ';
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {
        return ' ';
    }
}
