<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Value;

class TokenSelectorValueAttributeFunctionEquals extends TokenSelectorValueAttributeFunctionGeneric
{
    protected $operator = '=';
}