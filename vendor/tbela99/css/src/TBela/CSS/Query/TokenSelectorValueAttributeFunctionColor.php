<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Value;

class TokenSelectorValueAttributeFunctionColor implements TokenSelectorValueInterface
{
    use FilterTrait;

    protected $value = [];

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param array $value
     */
    public function __construct($value)
    {
        if (count($value->arguments) != 3) {

            $value->arguments = $this->trim($value->arguments);
        }

        if (isset($value->arguments[1]) && $value->arguments[1]->type == 'separator' && $value->arguments[1]->value == ',') {

            $value->arguments[1]->type = 'operator';
            $value->arguments[1]->value = '=';
        }

        if (count($value->arguments) != 3) {

            throw new InvalidArgumentException('expecting an array with 2 items', 400);
        }

        $value = $value->arguments;

        if (!in_array($value[0]->type, ['attribute_name', 'string']) ||
            $value[1]->type != 'operator' ||
            !in_array($value[2]->type, ['attribute_name', 'string'])) {

            throw new InvalidArgumentException('invalid input', 400);
        }

        if ($value[1]->value != '=') {

            throw new InvalidArgumentException(sprintf('unsupported operator "%s"', $value[1]->value), 400);
        }

        $options = [
            'compress' => true,
            'convert_color' => 'hex',
            'css_level' => 4
        ];

        if ($value[0]->type == 'string') {

            $value[0]->value = Value::parse($value[0]->value, 'color')->render($options);
        }

        if ($value[2]->type == 'string') {

            $value[2]->value = Value::parse($value[2]->value, 'color')->render($options);
        }

        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        $result = [];

        $options = [
            'compress' => true,
            'convert_color' => 'hex',
            'css_level' => 4
        ];

        foreach ($context as $element) {

            $value1 = $this->value[0]->type == 'attribute_name' ? $element[$this->value[0]->value] : $this->value[0]->value;
            $value2 = $this->value[2]->type == 'attribute_name' ? $element[$this->value[2]->value] : $this->value[2]->value;

            if ($value1 instanceof Value\Set) {

                $value1 = $value1->render($options);
            }

            if ($value2 instanceof Value\Set) {

                $value2 = $value2->render($options);
            }

            if ($value1 === $value2) {

                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options)
    {

        $result = 'color('.
            Value::parse($this->value[0]->value, 'color')->render($options).','.
            Value::parse($this->value[2]->value, 'color')->render($options).
        ')';

        return $result;
    }
}