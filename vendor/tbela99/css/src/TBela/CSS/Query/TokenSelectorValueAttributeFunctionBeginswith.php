<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Value;

class TokenSelectorValueAttributeFunctionBeginswith extends TokenSelectorValueAttributeFunctionGeneric
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $operator = '^=';
}