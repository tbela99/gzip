<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class OutlineColor extends Color
{
    use ValueTrait;

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null)
    {
        return $token->type == 'color';
    }
}
