<?php

namespace TBela\CSS\Query;

class TokenSelectorValueWhitespace implements TokenSelectorValueInterface
{
    use TokenTrait;

    public function __construct()
    {

    }

    /**
     * @param QueryInterface[] $context
     * @return array|bool
     */
    public function evaluate(array $context): array
    {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        return ' ';
    }
}