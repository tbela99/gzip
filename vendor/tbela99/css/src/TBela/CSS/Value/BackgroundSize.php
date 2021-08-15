<?php

namespace TBela\CSS\Value;

use TBela\CSS\Property\Config;
use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundSize extends Value
{

    use UnitTrait, ValueTrait;

    protected static $keywords = [
        'contain',
        'auto',
        'cover'
    ];

    protected static $defaults = ['auto', 'auto auto'];

    /**
     * @var array
     * @ignore
     */
    protected static $patterns = [

        'keyword',
        [
            ['type' => 'unit'],
            ['type' => 'unit', 'optional' => true]
        ]
    ];

    public static function matchKeyword($string, array $keywords = null)
    {
        $string = preg_replace('#\s+#', ' ', $string);

        if (trim($string) == 'auto auto') {

            $string = 'auto';
        }

        return parent::matchKeyword($string, $keywords);
    }

    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        return $token->type == 'unit' || ($token->type == 'css-string' && in_array($token->value, static::$keywords));
    }

    public static function reduce(array $tokens, array $options = [])
    {
        $tokens = parent::reduce($tokens, array_merge($options, ['remove_defaults' => false]));

        if (count($tokens) == 3 && static::matchDefaults($tokens[2]) && $tokens[1]->type == 'whitespace') {

            array_splice($tokens, 1, 2);
        }

        return $tokens;
    }
}
