<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundClip extends Value
{

    use UnitTrait, ValueTrait;

    /**
     * @var string[]
     */
    protected static $keywords = [
        'border-box',
        'padding-box',
        'content-box',
        'text'
    ];

    protected static $defaults = ['border-box'];
}
