<?php

namespace TBela\CSS\Query;

use Exception;
use TBela\CSS\Element\RuleList;

class TokenSelect extends Token implements TokenSelectInterface
{
    protected string $node = '';
    protected ?string $context = null;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function filter(array $context): array {

        if (empty($context)) {

            return [];
        }

        $result = [];

        switch ($this->node) {

            case '.':
                return $context;

            case '>':

                foreach ($context as $element) {

                    if ($element instanceof RuleList) {

                        array_splice($result, count($result), 0, $element->getChildren());
                    }
                }

                break;

            case '..':

                foreach ($context as $element) {

                    $result[] = $element->getParent();
                }

                return $result;

            case '/':

                return [$context[0]->getRoot()];

            case '*':

                if ($this->context == 'root') {

                   $context = [$context[0]->getRoot()];
                }

                $result = [];

                foreach ($context as $element) {

                    array_splice($result, count($result), 0, $this->select_all_nodes($element));
                }

                break;

            case 'self_or_descendants':

                $result = [];

                foreach ($context as $element) {

                    $result[] = $element;
                    array_splice($result, count($result), 0, $this->select_all_nodes($element));
                }

                break;

            default:

                throw new Exception(sprintf('Invalid select token "%s"', $this->node), 400);
        }

        return $this->unique($result);
    }

    /**
     * @param QueryInterface $element
     * @return QueryInterface[]
     */
    protected function select_all_nodes (QueryInterface $element): array {

        $result = [];

        if ($element instanceof RuleList) {

            foreach ($element->getChildren() as $child) {

                array_splice($result, count($result), 0, [$child]);
                array_splice($result, count($result), 0, $this->select_all_nodes($child));
            }
        }

        return array_values($result);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        if (isset($this->context)) {

            if ($this->context == 'root') {

                return '//';
            }
        }

        if($this->node == 'self_or_descendants') {

            return '';
        }

        if ($this->node == '>') {

            return '/';
        }

        return $this->node;
    }
}