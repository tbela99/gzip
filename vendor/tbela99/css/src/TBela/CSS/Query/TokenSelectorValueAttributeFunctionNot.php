<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunctionNot extends TokenSelectorValue implements TokenSelectorValueInterface
{
    protected $arguments = [];
    protected $name;
    protected $expressions;

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param object $value
     */
    public function __construct($value)
    {
        parent::__construct($value);

        $this->expressions = array_map([TokenSelectorValueAttribute::class, 'getInstance'], $value->arguments);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        $result = $context;

        foreach ($this->expressions as $filter) {

            $result = $filter->evaluate($result);
        }

        return array_diff($context, $result);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return 'not('.implode('', array_map(function ($expression) use($options) {

            return $expression->render($options);

            }, $this->expressions)).')';
    }
}