<?php

namespace TBela\CSS\Query;

use TBela\CSS\Parser\SyntaxError;
use function usort;

class Evaluator
{
    /**
     * @param string $expression
     * @param QueryInterface $context
     * @return QueryInterface[]
     * @throws SyntaxError
     */
    public function evaluate($expression, QueryInterface $context)
    {

        $tokenList = (new Parser())->parse($expression);

        $results = $tokenList->filter([$context]);

        $info = [];

        /**
         * @var \TBela\CSS\Element $element
         */
        foreach ($results as $key => $element) {

            $index = spl_object_hash($element);

            if (!isset($info[$index])) {

                $info[$index] = [
                    'key' => $key,
                    'depth' => [],
                    'name' => is_null($element['name']) ? implode(',', (array) $element['selector']) : $element['name'],
                    'val' => (string) $element
                ];

                $el = $element;

                while ($el && ($parent = $el->getParent())) {

                    $info[$index]['depth'][] = array_search($el, $parent->getChildren(), true);
                    $el = $parent;
                }

                $info[$index]['depth'] = implode('', array_reverse($info[$index]['depth']));
            }
        }

        usort($info, function ($a, $b) {

            if ($a['depth'] < $b['depth']) {

                return -1;
            }

            if ($a['depth'] > $b['depth']) {

                return 1;
            }

            return 0;
        });

        $res = [];

        foreach ($info as $value) {

            $res[] = $results[$value['key']];
        }

        return $res;
    }
}