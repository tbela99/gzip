<?php

namespace TBela\CSS\Query;

interface TokenSelectorValueInterface
{

    /**
     * @param QueryInterface[] $context
     * @return array
     */
    public function evaluate(array $context): array;

    /**
     * @param array $options
     * @return string
     */
    public function render(array $options);
}