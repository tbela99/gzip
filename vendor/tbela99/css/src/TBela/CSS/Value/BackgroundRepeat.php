<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundRepeat extends Value
{

    use ValueTrait;

    /**
     * @var string[]
     */
    protected static $keywords = [
        'repeat-x',
        'repeat-y',
        'repeat',
        'space',
        'round',
        'no-repeat'
    ];

    /**
     * @var string[]
     */
    protected static $keymap = [
        'repeat no-repeat' => 'repeat-x',
        'no-repeat repeat' => 'repeat-y',
        'repeat repeat' => 'repeat',
        'space space' => 'space',
        'round round' => 'round',
        'no-repeat no-repeat' => 'no-repeat'
    ];

    protected static $patterns = [
        'keyword',
        [
            ['type' => 'background-repeat'],
            ['type' => 'background-repeat', 'optional' => true]
        ]
    ];

    /**
     * @var string[]
     */
    protected static $defaults = ['repeat'];

    public static function matchKeyword($string, array $keywords = null)
    {

        $key = preg_replace('~(\s+)~', ' ', trim($string));

        if (isset(static::$keymap[$key])) {

            return static::$keymap[$key];
        }

        return parent::matchKeyword($string, $keywords);
    }
}
