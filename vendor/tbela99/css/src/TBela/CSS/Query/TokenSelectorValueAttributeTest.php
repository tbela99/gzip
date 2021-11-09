<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeTest extends TokenSelectorValueAttribute
{
    protected string $name;

    /**
     * TokenSelectorValueAttributeTest constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function evaluate(array $context): array
    {
        $result = [];

        foreach ($context as $element) {

            if ((is_callable([$element, 'offsetExists']) && call_user_func([$element, 'offsetExists'], $this->name)) ||
                !is_null($element[$this->name])) {

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

        return '@'.$this->name;
    }
}