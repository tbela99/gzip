<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundColor extends Color
{

    public static $defaults = ['transparent', '#0000'];

    /**
     * @inheritDoc
     */
    public static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '')
    {
        $tokens = [];

        foreach (parent::getTokens($string, $capture_whitespace, $context, $contextName) as $token) {

            if ($token->type == 'color') {

                $token->type = static::type();
            }

            $tokens[] = $token;
        }

        return static::reduce($tokens);
    }

    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {
        return $token->type == 'color';
    }
}
