<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundAttachment extends ShortHand
{

    protected static $keywords = [
        'fixed',
        'local',
        'scroll'
    ];

    protected static $defaults = ['scroll'];

    /**
     * @var array
     * @ignore
     */
    protected static $patterns = [

        'keyword'
    ];
}
