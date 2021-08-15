<?php

namespace TBela\CSS\Value;

use TBela\CSS\ArrayTrait;
use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundOrigin extends Value
{

    use UnitTrait, ValueTrait;

    /**
     * @var string[]
     */
    protected static $keywords = [
        'border-box',
        'padding-box',
        'content-box'
    ];

    protected static $defaults = ['padding-box'];
}
