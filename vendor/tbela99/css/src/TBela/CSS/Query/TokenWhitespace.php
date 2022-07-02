<?php

namespace TBela\CSS\Query;

class TokenWhitespace implements TokenSelectorValueInterface
{
    protected string $type = 'whitespace';

    /**
     * @inheritDoc
     */
    public function evaluate(array $context): array {

       return $context;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return ' ';
    }
}