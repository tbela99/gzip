<?php

namespace TBela\CSS\Value;

// pattern font-style font-variant font-weight font-stretch font-size / line-height <'font-family'>

/**
 * parse font
 * @package TBela\CSS\Value
 */
class Font extends ShortHand
{
    /**
     * @var array
     * @ignore
     */
    protected static $keywords = ['unset', 'inherit', 'caption', 'icon', 'menu', 'message-box', 'small-caption', 'status-bar'];

    /**
     * @var array
     * @ignore
     */
    protected static $patterns = [

        'keyword',
        [
            ['type' => 'font-weight', 'optional' => true],
            ['type' => 'font-style', 'optional' => true],
            ['type' => 'font-variant', 'optional' => true, 'match' => 'keyword', 'keywords' => ['normal', 'small-caps']],
            ['type' => 'font-stretch', 'optional' => true],
            ['type' => 'font-size'],
            ['type' => 'line-height', 'optional' => true, 'prefix' => '/', 'previous' => 'font-size'],
            ['type' => 'font-family', 'multiple' => true, 'separator' => ',']
        ]
    ];
}
