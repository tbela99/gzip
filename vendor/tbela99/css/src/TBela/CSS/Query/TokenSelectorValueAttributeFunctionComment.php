<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunctionComment extends TokenSelectorValue implements TokenSelectorValueInterface
{
    protected array $arguments = [];
    protected string $name;
    protected TokenSelectorValueInterface $expression;

    /**
     * @inheritDoc
     */
    public function evaluate(array $context): array
    {
       return array_filter($context, function (QueryInterface $element) {

           return $element['type'] == 'Comment';
       });
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return 'comment()';
    }
}