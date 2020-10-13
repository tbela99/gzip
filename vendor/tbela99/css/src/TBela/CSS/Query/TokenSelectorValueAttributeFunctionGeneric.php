<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Value;

class TokenSelectorValueAttributeFunctionGeneric implements TokenSelectorValueInterface
{
    use FilterTrait;

    /**
     * @var TokenSelectorValueAttributeExpression
     */
    protected $expression;
    /**
     * @var string
     */
    protected $operator;

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param \stdClass $value
     */
    public function __construct($value)
    {

        if (count($value->arguments) != 3) {

            $value->arguments = $this->trim($value->arguments);
        }

        // function
        if (isset($value->arguments[1]) && $value->arguments[1]->type == 'separator' && $value->arguments[1]->value == ',') {

            $value->arguments[1]->type = 'operator';
            $value->arguments[1]->value = $this->operator;
        }

        $this->expression = new TokenSelectorValueAttributeExpression($value->arguments);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context) {

       return $this->expression->evaluate($context);
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {
        return $this->expression->render($options);
    }
}