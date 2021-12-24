<?php

namespace TBela\CSS\Query;

use TBela\CSS\Element\AtRule;
use TBela\CSS\Element\Rule;

/**
 * Evaluate node name
 * @package TBela\CSS\Query
 */
class TokenSelectorValueString extends TokenSelectorValue
{
    use TokenStringifiableTrait;

    protected $q = '';
    protected $value = '';
    protected $isAtRule = false;

    public function __construct($data)
    {
        parent::__construct($data);

        $this->isAtRule = substr($this->value, 0, 1) == '@';
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        if ($this->value == '*') {

            return $context;
        }

        $result = [];

        foreach ($context as $element) {

            if ($this->isAtRule && !($element instanceof AtRule)) {

                continue;
            }

            if ($element instanceof Rule) {

                if (in_array($this->value, $element->getSelector())) {

                    $result[] = $element;
                }
            }
            else {

                $name = ($element instanceof AtRule ? '@' : '').$element['name'];

                if($this->value === $name) {

                    $result[] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = []) {

        return $this->q.$this->value.$this->q;
    }
}