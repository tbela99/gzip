<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * CSS comment value
 * @package TBela\CSS\Value
 */
class Comment extends Value {

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        return true;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        if (!empty($options['compress']) || !empty($options['remove_comments'])) {

            return '';
        }

        return $this->data->value;
    }
}
