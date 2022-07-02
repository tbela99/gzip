<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class FontWeight extends Value
{
    protected static $keywords = [
        'thin' => '100',
        'hairline' => '100',
        'extra light' => '200',
        'ultra light' => '200',
        'light' => '300',
        'normal' => '400',
        'regular' => '400',
        'medium' => '500',
        'semi bold' => '600',
        'demi bold' => '600',
        'bold' => '700',
        'extra bold' => '800',
        'ultra bold' => '800',
        'black' => '900',
        'heavy' => '900',
        'extra black' => '950',
        'ultra black' => '950',
        'lighter' => 'lighter',
        'bolder' => 'bolder'
    ];

    protected static $defaults = ['normal', '400', 'regular'];

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        return static::doRender($this->data, $options);
    }

    public static function doRender($data, array $options = [])
    {

        $value = static::matchKeyword($data->value);

        if (!empty($options['compress'])) {

            if (isset(static::$keywords[$value])) {

                $value = static::$keywords[$value];
            }

            if (is_numeric($value)) {

                return Number::compress($value);
            }
        }

        if (array_key_exists($value, static::$keywords) && strpos($value, ' ') !== false) {

            return '"' . $value . '"';
        }

        return $data->value;
    }

    /**
     * @inheritDoc
     */
    public static function match($data, $type)
    {

        return $type == 'font-weight';
    }

    /**
     * @inheritDoc
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        if ($token->type == 'number' && $token->value > 0 && $token->value <= 1000) {

            return true;
        }

        if (isset($token->value)) {

            $matchKeyWord = static::matchKeyword($token->value);

            if (!is_null($matchKeyWord)) {

                return true;
            }
        }

        return $token->type == static::type();
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    protected static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {

        $type = static::type();

        $matchKeyword = static::matchKeyword($string);

        if (!is_null($matchKeyword)) {

            return [(object)['type' => $type, 'value' => $matchKeyword]];
        }

        $tokens = static::getTokens($string, $capture_whitespace, $context, $contextName);

        foreach ($tokens as $token) {

            if (static::matchToken($token)) {

                if ($token->type == 'css-string') {

                    $value = static::matchKeyword($token->value);

                    if (!is_null($value)) {

                        $token->value = $value;
                    }
                }

                $token->type = $type;
            }
        }

        return static::reduce($tokens);
    }

    public static function keywords()
    {

        return array_keys(static::$keywords);
    }
}
