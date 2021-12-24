<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttribute extends TokenSelectorValue
{
    use FilterTrait, TokenStringifiableTrait;

    protected $value = [];
    protected $expression;

    /**
     * TokenSelectorValueAttribute constructor.
     * @param object $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        parent::__construct($data);

        if (count($data->value) > 3) {

            $data->value = $this->trim($data->value);
        }

        if (count($data->value) == 3) {

            if ($data->value[0]->type == 'string' &&
                $data->value[1]->type == 'operator' &&
                $data->value[2]->type == 'string') {

                $this->expression = new TokenSelectorValueAttributeSelector($data->value);
            }

            else {

                $this->expression = new TokenSelectorValueAttributeExpression($data->value);
            }
        }

        else if (count($data->value) == 1) {

            if (!isset($data->value[0]->name) && $data->value[0]->type == 'attribute_name') {

                    $this->expression = new TokenSelectorValueAttributeTest($data->value[0]->value);
                }

            else {

                $this->expression = call_user_func([TokenSelectorValueAttribute::class, 'getInstance'], $data->value[0]);
            }
        }

        else {

            throw new \Exception(sprintf('attribute not implemented %s', var_export($data, true)), 501);
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
     * @param array $options
     * @return string
     */
    public function render(array $options = []) {

        return '['.$this->expression->render($options).']';
    }
}