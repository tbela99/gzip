<?php

namespace TBela\CSS\Query;

class TokenSelectorValueSeparator extends Token implements TokenSelectorValueInterface
{

    use TokenStringifiableTrait;

    /**
     * @var string
     */
    protected $value = '';

    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function filter(array $context): array
    {

        return $context;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context): array
    {

        return $context;
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = []) {

        if (!empty($options['compress'])) {

            return $this->value;
        }

        return sprintf($this->value == ',' ? '%s ' : ' %s ', $this->value);
    }
}