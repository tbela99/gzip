<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;

class TokenSelectorValueAttributeExpression implements TokenSelectorValueInterface
{
    use FilterTrait;

    protected $value = [];

    /**
     * TokenSelectorValueAttributeExpression constructor.
     * @param array $value
     */
    public function __construct(array $value)
    {

        if (count($value) != 3) {

            $value = $this->trim($value);
        }

        if (count($value) != 3) {

            throw new InvalidArgumentException('expecting an array with 2 items ', 400);
        }

        if (!in_array($value[0]->type, ['attribute_name', 'string']) ||
            $value[1]->type != 'operator' ||
            !in_array($value[2]->type, ['attribute_name', 'string'])) {

            throw new InvalidArgumentException('invalid input', 400);
        }

        if (!in_array($value[1]->value, ['=', '^=', '*=', '$=', '~='])) {

            throw new InvalidArgumentException(sprintf('unsupported operator "%s"', $value[1]->value), 400);
        }

        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        $result = [];

        foreach ($context as $element) {

            $value1 = $this->value[0]->type == 'attribute_name' ? (string) $element[$this->value[0]->value] : $this->value[0]->value;
            $value2 = $this->value[2]->type == 'attribute_name' ? (string) $element[$this->value[2]->value] : $this->value[2]->value;
            $match = false;

            switch ($this->value[1]->value) {

                case '=':

                    $match = $value1 === $value2;
                    break;

                case '^=':

                    $match = substr($value1, 0, strlen($value2)) === $value2;
                    break;

                case '*=':

                    $match = strpos($value1, $value2) !== false;
                    break;

                case '$=':

                    $match = substr($value1, - strlen($value2)) === $value2;
                    break;
            }

            if ($match) {

                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @param array $options
     * @return string
     */
    public function render(array $options = []) {

        $result = '';

        foreach ($this->value as $value) {

            if ($value->type == 'attribute_name') {

                $result .= '@';
            }

            $q = isset($value->q) ? $value->q : '';

            $result .= $q.$value->value.$q;
        }

        return $result;
    }
}