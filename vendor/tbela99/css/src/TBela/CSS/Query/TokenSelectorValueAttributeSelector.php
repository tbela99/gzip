<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Element\Rule;
use TBela\CSS\Value;

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

            throw new InvalidArgumentException('expecting an array with 3 items', 400);
        }

        if ($value[0]->type != 'string' ||
            $value[1]->type != 'operator' ||
            $value[2]->type != 'string') {

            throw new InvalidArgumentException('invalid input', 400);
        }

        if (!in_array($value[1]->value, ['=', '^=', '*=', '$='])) {

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

        $value = $this->value[2]->value;

        foreach ($context as $element) {

            if ($element instanceof Rule) {

                foreach ($element->getSelector() as $val) {

                    if ($this->value[0]->value != 'value') {

                        continue;
                    }

                    $attr = is_string($val) ? $val : Value::renderTokens([$val]);

                    switch ($this->value[1]->value) {

                        case '=':

                            if ($value == $attr) {

                                $result[] = $element;
                                continue 2;
                            }

                            break;

                        case '^=':

                            if (strpos($attr, $value) === 0) {

                                $result[] = $element;
                                continue 2;
                            }

                            break;

                        case '*=':

                            if (strpos($attr, $value) !== false) {

                                $result[] = $element;
                                continue 2;
                            }

                            break;

                        case '$=':

                            if (substr($attr, -strlen($value)) === $value) {

                                $result[] = $element;
                                continue 2;
                            }

                            break;
                    }
                }
            }

            else {

                $attr = (string) $element[$this->value[0]->value];

                switch ($this->value[1]->value) {

                    case '=':

                        if ($value == $attr) {

                            $result[] = $element;
                        }

                        break;

                    case '^=':

                        if (strpos($attr, $value) === 0) {

                            $result[] = $element;
                        }

                        break;

                    case '*=':

                        if (strpos($attr, $value) !== false) {

                            $result[] = $element;
                        }

                        break;

                    case '$=':

                        if (substr($attr, -strlen($value)) === $value) {

                            $result[] = $element;
                        }

                        break;
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