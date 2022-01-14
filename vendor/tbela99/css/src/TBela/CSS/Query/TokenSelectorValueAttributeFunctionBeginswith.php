<?php

namespace TBela\CSS\Query;

class TokenSelectorValueAttributeFunctionBeginswith extends TokenSelectorValueAttributeFunctionGeneric
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $operator = '^=';
}