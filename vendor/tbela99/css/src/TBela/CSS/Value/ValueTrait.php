<?php

namespace TBela\CSS\Value;

// pattern font-style font-variant font-weight font-stretch font-size / line-height <'font-family'>
use TBela\CSS\Property\Config;
use TBela\CSS\Value;

/**
 * parse font
 * @package TBela\CSS\Value
 */
trait ValueTrait
{

    /**
     * @inheritDoc
     */
    protected static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {

        $type = static::type();

        $separator = Config::getPath('properties.'.$type.'.separator');

        $strings = is_null($separator) ? [$string] : static::split($string, $separator);
        $result = [];

        foreach ($strings as $string) {

            if (!empty(static::$keywords)) {

                $keyword = static::matchKeyword($string);

                if (!is_null($keyword)) {

                    $result[] = [(object) ['type' => $type, 'value' => $keyword]];
                    continue;
                }
            }

            $tokens = static::getTokens($string, $capture_whitespace, $context, $contextName);

            foreach ($tokens as $token) {

                if ($token->type == 'css-string') {

                    $keyword = static::matchKeyword($token->value);

                    if (!is_null($keyword)) {

                        $token->type = static::type();
                        unset($token->q);
                        continue;
                    }
                }

                if (static::matchToken($token)) {

                    $token->type = $type;
                }
            }

            $result[] = static::reduce($tokens);
        }

        if (count($result) == 1) {

            return $result[0];
        }

        $i = 0;
        $j = count($result) - 1;

        $set = $result[0];

        while (++$i <= $j) {

            $set[] = (object) ['type' => 'separator', 'value' => $separator];
            array_splice($set, count($set), 0, $result[$i]);
        }

        return $set;
    }
}
