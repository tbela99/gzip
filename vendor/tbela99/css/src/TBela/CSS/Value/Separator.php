<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css separator token
 * @package TBela\CSS\Value
 */
class Separator extends Value
{
    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        return $this->data->value;
    }
}
