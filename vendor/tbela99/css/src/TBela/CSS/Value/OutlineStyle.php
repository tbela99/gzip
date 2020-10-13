<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class OutlineStyle extends Value
{

    use ValueTrait;

    protected static $keywords = [
        'auto',
        'none',
        'dotted',
        'dashed',
        'solid',
        'double',
        'groove',
        'ridge',
        'inset',
        'outset'
    ];
}
