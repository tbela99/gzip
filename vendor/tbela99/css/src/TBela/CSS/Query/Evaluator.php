<?php

namespace TBela\CSS\Query;
use TBela\CSS\Parser\SyntaxError;
use TBela\CSS\Value;
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

        return $this->sortNodes($tokenList->filter([$context]));

    }

    /**
     * search nodes by class ame
     * @param string $classNames a comma separated list of class names
     * @param QueryInterface $context
     * @return array
     * @throws SyntaxError
     */
    public function evaluateByClassName($classNames, QueryInterface $context)
    {

        $parser = new Parser();

        $selectors = [];

        foreach ($parser->split($classNames) as $className) {

            foreach ($parser->split($className, ',') as $selector) {

                $selector = trim($selector);

                $selectors[$selector] = (string) $parser->parse($selector);
            }
        }

        $selectors = array_values($selectors);

        sort($selectors);

        $result = [];

        $stack = $context->getType() == 'Stylesheet' ? $context->getChildren() : [$context];

        while($node = array_shift($stack)) {

            if ($node->getType() == 'Rule') {

                /**
                 * @var \TBela\CSS\Element\Rule $node
                 */

                if ($this->search($selectors, $node->getSelector())) {

                        $result[] = $node;
                }
            }

            /**
             * @var \TBela\CSS\Element\AtRule $node
             */

            else if ($node->getType() == 'AtRule') {

                $value = $node->getValue();
                if ($this->search($selectors, [trim('@'.$node->getName().' '.(is_string($value) ? $value : Value::renderTokens($node->getValue(), ['remove_comments' => true])))])) {

                    $result[] = $node;
                }

                if (!$node->isLeaf() && !$node->hasDeclarations()) {

                    array_splice($stack, count($stack), 0, $node->getChildren());
                }
            }
        }

        return $result;
    }

    /**
     * @param array $selectors
     * @param array $search
     * @return bool
     * @ignore
     */
    protected function search(array $selectors, array $search)
    {

        $l = count($search);

        while ($l--) {

            $k = count($selectors) - 1;
            $i = 0;

            while (true) {

                $j = $i + ceil(($k - $i) / 2);

                if ($selectors[$j] < $search[$l]) {

                    if ($i == $j) {

                        break;
                    }

                    $i = $j;

                } else if ($selectors[$j] > $search[$l]) {

                    if ($k == $j) {

                        if ($selectors[$i] === $search[$l]) {

                            return true;
                        }

                        break;
                    }

                    $k = $j;

                } else if ($selectors[$j] === $search[$l]) {

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \TBela\CSS\Interfaces\ElementInterface[] $nodes
     * @return array
     * @ignore
     */
    protected function sortNodes($nodes)
    {

        $info = [];

        foreach ($nodes as $key => $element) {

            $index = spl_object_hash($element);

            if (!isset($info[$index])) {

                $info[$index] = [
                    'key' => $key,
                    'depth' => []
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

            $res[] = $nodes[$value['key']];
        }

        return $res;
    }
}