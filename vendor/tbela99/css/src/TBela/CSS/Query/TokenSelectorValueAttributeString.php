<?php

namespace TBela\CSS\Query;

use TBela\CSS\Element\AtRule;
use TBela\CSS\Element\Rule;

class TokenSelectorValueAttributeString implements TokenSelectorValueInterface
{
    protected string $value;

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value->value;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context): array
    {
        $result = [];

        $value = '['.$this->value.']';

        foreach ($context as $element) {

            if ($element instanceof Rule) {

                if (in_array($value, $element->getSelector())) {

                    $result[] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return $this->value;
    }
}