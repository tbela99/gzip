<?php

namespace TBela\CSS\Value;

// pattern font-style font-variant font-weight font-stretch font-size / line-height <'font-family'>

/**
 * parse font
 * @package TBela\CSS\Value
 */
trait ValueTrait
{

    /**
     * @inheritDoc
     */

    protected static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '')

    {

        $type = static::type();
        $tokens = static::getTokens($string, $capture_whitespace, $context, $contextName);

        foreach ($tokens as $key => $token) {

            if (static::matchToken($token)) {

                $token->type = $type;
            }
        }

        return new Set(static::reduce($tokens));
    }
}
