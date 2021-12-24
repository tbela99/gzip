<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunctionComment extends TokenSelectorValue implements TokenSelectorValueInterface
{
    protected $arguments = [];
    protected $name;
    protected $expression;

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
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