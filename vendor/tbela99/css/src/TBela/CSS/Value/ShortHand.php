<?php

namespace TBela\CSS\Value;

use \Exception;
use TBela\CSS\Property\Config;
use \TBela\CSS\Value;

/**
 * parse shorthand
 * @package TBela\CSS\Value
 */
class ShortHand extends Value
{
    /**
     * @var array
     * @ignore
     */
    protected static $patterns = [

        /*
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
        */
    ];

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected static function doParse(string $string, bool $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {

        $separator = Config::getPath('map.'.static::type().'.separator');
        $results = [];

        foreach ((is_null($separator) ? [$string] : static::split($string, $separator)) as $string) {

            $keyword = static::matchKeyword($string);

            if (!is_null($keyword)) {

                $results[] = [(object)['value' => $keyword, 'type' => static::type()]];
                break;
            }

            $tokens = static::getTokens($string, $capture_whitespace, $context, $contextName);
            $results[] = static::reduce(static::matchPattern($tokens));
        }

        $j = count($results) - 1;
        $i = -1;

        $set = [];

        while (++$i < $j) {

            array_splice($set, count($set), 0, $results[$i]);
            $set[] = (object) ['type' => 'separator', 'value' => $separator];
        }

        array_splice($set, count($set), 0, $results[$j]);
        return $set;
    }

    /**
     * @param array $tokens
     * @return array
     * @throws Exception
     */
    public static function matchPattern(array $tokens)
    {

        foreach (static::$patterns as $patterns) {

            if (is_string($patterns)) {

                continue;
            }

            $j = count($tokens);
            $previous = null;
            $next = null;

            for ($i = 0; $i < $j; $i++) {

                if (!isset($tokens[$i]->type)) {

                    echo new Exception('empty type not allowed');
                }

                if (in_array($tokens[$i]->type, ['separator', 'whitespace'])) {

                    continue;
                }

                // is this a valid font definition?
                foreach ($patterns as $key => $pattern) {

                    $className = static::getClassName($pattern['type']) . '::matchToken';

                    $k = $i + 1;
                    $next = isset($tokens[$k]) ? $tokens[$k] : null;

                    while (!is_null($next)) {

                        if (!in_array($next->type, ['separator', 'whitespace'])) {

                            break;
                        }

                        $next = isset($tokens[++$k]) ? $tokens[$k] : null;
                    }

                    if (call_user_func($className, $tokens[$i], isset($tokens[$i - 1]) ? $tokens[$i - 1] : null, $previous, isset($tokens[$i + 1]) ? $tokens[$i + 1] : null, $next, $i, $tokens)) {

                        $tokens[$i]->type = $pattern['type'];
                        $previous = $tokens[$i];

                        $k = $i;

                        if (!empty($pattern['multiple'])) {

                            while (++$k < $j) {

                                if (in_array($tokens[$k]->type, ['separator', 'whitespace'])) {

                                    continue;
                                }

                                $w = $k;

                                while (!is_null($next)) {

                                    if (!in_array($next->type, ['separator', 'whitespace'])) {

                                        break;
                                    }

                                    $next = isset($tokens[++$w]) ? $tokens[1 + $w] : null;
                                }

                                if (call_user_func($className, $tokens[$k], $tokens[$k - 1], $previous, isset($tokens[$k + 1]) ? $tokens[$k + 1] : null, $next, $k, $tokens)) {

                                    $tokens[$k]->type = $pattern['type'];
                                    $i = $k;
                                    $previous = $tokens[$k];

                                    $w = $k;
                                    $next = isset($tokens[$k + 1]) ? $tokens[$k + 1] : null;

                                    while (!is_null($next)) {

                                        if (!in_array($next->type, ['separator', 'whitespace'])) {

                                            break;
                                        }

                                        $next = isset($tokens[++$w]) ? $tokens[$w] : null;
                                    }
                                }

                                else {

                                    break;
                                }
                            }

                            $previous = isset($tokens[$i - 1]) ? $tokens[$i - 1] : null;
                        }

                        unset($patterns[$key]);
                        break;
                    }
                    // failure to match a mandatory property
                    else if (empty($pattern['optional'])) {

                        break;
                    }
                }
            }

            $mandatory = array_values(array_filter($patterns, function ($pattern) {

                return empty($pattern['optional']);
            }));

            if (!empty($mandatory)) {

                throw new Exception(' Invalid "' . static::type() . '" definition, missing \'' . $mandatory[0]['type'] . '\' in "'.Value::renderTokens($tokens).'"', 400);
            }

            break;
        }

        return $tokens;
    }
}
