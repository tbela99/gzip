<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunction extends TokenSelectorValue implements TokenSelectorValueInterface
{
    protected $arguments = [];
    protected $name;
    protected $expression;

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param object $value
     */
    public function __construct($value)
    {
        parent::__construct($value);

        $this->arguments = [];

        if (in_array($this->name, ['contains', 'beginswith', 'endswith', 'equals']) && count($value->arguments) == 3 && $value->arguments[1]->type == 'separator' && $value->arguments[1]->value == ',') {

            $op = '';

            switch ($this->name) {

                case 'contains':

                    $op = '*=';
                    break;
                case 'beginswith':

                    $op = '^=';
                    break;
                case 'endswith':

                    $op = '$=';
                    break;
                case 'equals':

                    $op = '=';
                    break;
            }
            // use TokenSelectorValueAttributeExpression
            $value->arguments[1] = (object) ['type' => 'operator', 'value' => $op];
            $this->expression = new TokenSelectorValueAttributeExpression($value->arguments);
        }

        else {

            // map to an existing function or die
            $this->expression = call_user_func([static::class, 'getInstance'], (object) ['type' => $value->name, 'arguments' => $value->arguments]);
        }
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
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