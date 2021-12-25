<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;

class TokenSelectorValueAttributeSelector implements TokenSelectorValueInterface
{
    use FilterTrait;

    protected $value = [];
    protected $_value = '';

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

            throw new InvalidArgumentException('expecting an array with 2 items', 400);
        }

        if ($value[0]->type != 'string' ||
            $value[1]->type != 'operator' ||
            $value[2]->type != 'string') {

            throw new InvalidArgumentException('invalid input', 400);
        }

        if (!in_array($value[1]->value, ['=', '^=', '*=', '~=', '$='])) {

            throw new InvalidArgumentException(sprintf('unsupported operator "%s"', $value[1]->value), 400);
        }

        $this->value = $value;
        $this->_value = '['.$this->render().']';
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        $result = [];

        foreach ($context as $element) {

            foreach ((isset($element['selector'] ) ? $element['selector']  : []) as $val) {

                if (strpos((string) $val, $this->_value) !== false) {

                    $result[] = $element;
                }
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

            $q = isset($value->q) ? $value->q : '';

            $result .= $q.$value->value.$q;
        }

        return $result;
    }
}