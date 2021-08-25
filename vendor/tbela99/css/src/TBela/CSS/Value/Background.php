<?php

namespace TBela\CSS\Value;

use TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class Background extends ShortHand
{

    public static $keywords = ['none'];

    /**
     * @var array
     * @ignore
     */
    protected static $patterns = [

        'keyword',
        [
            // <bg-image> || <bg-position> [ / <bg-size> ]? || <repeat-style> || <attachment> || <box> || <box>
            ['type' => 'background-image', 'optional' => true],
            ['type' => 'background-color', 'optional' => true],
            ['type' => 'background-position', 'multiple' => true, 'optional' => true],
            [
                'type' => 'background-size', 'multiple' => true, 'optional' => true,
                'prefix' => [
                    ['type' => 'background-position'],
                    '/'
                ]
            ],
            ['type' => 'background-repeat', 'multiple' => true, 'optional' => true],
            ['type' => 'background-attachment', 'optional' => true],
            ['type' => 'background-clip', 'optional' => true],
            ['type' => 'background-origin', 'multiple' => true, 'optional' => true]
        ]
    ];

    /**
     * @inheritDoc
     */
    public static function matchPattern(array $tokens)
    {

        $tokens = static::reduce(parent::matchPattern($tokens));

        $result = [];

        for ($i = 0; $i < count($tokens); $i++) {

            if (in_array($tokens[$i]->type, ['separator', 'whitespace'])) {

                $result[] = $tokens[$i];
                continue;
            }

            $k = $i;
            $j = count($tokens);
            $matches = [$tokens[$i]];

            while (++$k < $j) {

                if ($tokens[$k]->type == 'whitespace') {

                    continue;
                }

                if ($tokens[$k]->type != $tokens[$i]->type) {

                    $k = $k - 1;

                    if (count($matches) == 1) {

                        array_splice($result, count($result), 0, array_slice($tokens, $i, $k - $i + 1));
                        $i = $k;
                        continue 2;
                    }

                    break;
                } else {

                    $matches[] = $tokens[$k];
                }
            }

            $slice = array_slice($tokens, $i, $k - $i + 1);
            $className = static::getClassName($slice[0]->type);
            $keyword = $className::matchKeyword(implode('', array_map(Value::class . '::getInstance', $slice)));

            if (!is_null($keyword)) {

                $result[] = (object)['type' => $tokens[$i]->type, 'value' => $keyword];
            } else {

                array_splice($result, count($result), 0, array_slice($tokens, $i, $k - $i + 1));
            }

            $i = $k;
        }

        return $result;
    }
}
