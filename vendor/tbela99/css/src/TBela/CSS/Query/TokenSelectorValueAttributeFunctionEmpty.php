<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunctionEmpty extends TokenSelectorValue implements TokenSelectorValueInterface
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

           return is_callable([$element, 'getChildren']) && is_null($element['firstChild']);
       });
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return 'empty()';
    }
}