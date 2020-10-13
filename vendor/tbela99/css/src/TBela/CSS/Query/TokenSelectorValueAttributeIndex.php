<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeIndex implements TokenSelectorValueInterface
{
    protected $value;

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
    public function evaluate(array $context)
    {
        return isset($context[$this->value - 1]) ? [$context[$this->value - 1]] : [];
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return $this->value;
    }
}