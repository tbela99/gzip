<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class OutlineWidth extends Unit
{
    use UnitTrait;

    protected static $keywords = [
        'thin',
        'medium',
        'thick'
    ];

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        return $token->type == 'unit' || ($token->type == 'number' && $token->value == 0);
    }
}
