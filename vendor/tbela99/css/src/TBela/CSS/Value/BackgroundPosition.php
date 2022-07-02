<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundPosition extends Value
{

    use ValueTrait;

    protected static $keywords = ['left', 'center', 'right', 'top', 'bottom'];

    protected static $x = ['left', 'right'];

    protected static $y = ['top', 'bottom'];

    public static $patterns = [
        [
            [
                'type' => 'background-position', 'multiple' => true
            ]
        ],
        [
            [
                'type' => 'background-position-x'
            ],
            [
                'type' => 'background-position-x', 'optional' => true
            ],
            [
                'type' => 'background-position-y', 'optional' => true
            ],
            [
                'type' => 'background-position-y', 'optional' => true
            ]
        ],
        [
            [
                'type' => 'background-position-y'
            ],
            [
                'type' => 'background-position-y', 'optional' => true
            ],
            [
                'type' => 'background-position-x', 'optional' => true
            ],
            [
                'type' => 'background-position-x', 'optional' => true
            ]
        ]
    ];

    protected static $defaults = ['0 0'];

    /**
     * @inheritDoc
     */
    protected function __construct($data)
    {

        parent::__construct($data);
        unset($this->data->q);
    }

    /**
     * @inheritDoc
     */

    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        $test = false;

        if ($token->type == 'number' && $token->value == "0") {

            $test = true;
        } else if ($token->type == 'css-string') {

            $test = in_array($token->value, static::$keywords);
        } else {

            $test = $token->type == 'unit' && (is_null($previousValue) || in_array($previousValue->type, ['css-url', 'color', 'background-color', 'background-image', 'background-position']));
        }

        if (!$test) {

            return false;
        }

        $values = [];

        while ($index-- && count($values) < 4) {

            if ($tokens[$index]->type == 'whitespace') {

                continue;
            }

            if ($tokens[$index]->type != 'background-position') {

                break;
            }

            array_unshift($values, $tokens[$index]);
        }

        if (count($values) == 4) {

            return false;
        }

        switch (count($values)) {

            // two values
            case 1;

                // must not be the same coordinates
                if (in_array($values[0]->value, static::$x)) {

                    return !in_array($token->value, static::$x);
                }

                // must not be the same coordinates
                if (in_array($values[0]->value, static::$y)) {

                    return !in_array($token->value, static::$y);
                }

                return true;

            // three values
            case 2:

                // must be a keyword
                if (!in_array($values[0]->value, static::$keywords) ||
                    // must not be the same coordinates
                    !static::check(static::$x, $values[0]->value, $values[1]->value, $token->value) ||
                    !static::check(static::$y, $values[0]->value, $values[1]->value, $token->value) ||
                    !static::check(static::$x, $values[1]->value, $token->value) ||
                    !static::check(static::$y, $values[1]->value, $token->value)) {

                    return false;
                }

                return true;

            // four values
            case 3:

                // invalid
                if (!in_array($values[0]->value, static::$keywords) ||
                    in_array($values[1]->value, static::$keywords) ||
                    in_array($token->value, static::$keywords)) {

                    return false;
                }

                // must not be the same coordinates
                if (!static::check(static::$x, $values[0]->value, $values[2]->value) ||
                    !static::check(static::$y, $values[0]->value, $values[2]->value)) {

                    return false;
                }

                return true;
        }

        return count($values) == 0;
    }

    /**
     * check that $values do not belong to the same set as $value
     * @param array $set
     * @param $value
     * @param mixed ...$values
     * @return bool
     * @ignore
     */
    protected static function check(array $set, $value, ...$values)
    {

        if (in_array($value, $set)) {

            foreach ($values as $value) {

                if (in_array($value, $set)) {

                    return false;
                }
            }
        }

        return true;
    }

    public function render(array $options = [])
    {

        return static::doRender($this->data, $options);
    }

    public static function doRender($data, array $options = []) {

        if (isset($data->unit)) {

            if ($data->value == '0') {

                return '0';
            }

            if (!empty($options['compress'])) {

                return Number::compress($data->value) . $data->unit;
            }

            return $data->value . $data->unit;
        }

        return $data->value;
    }
}
