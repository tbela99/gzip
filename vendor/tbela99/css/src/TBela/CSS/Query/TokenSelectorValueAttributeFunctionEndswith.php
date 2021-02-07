<?php

namespace TBela\CSS\Query;

use InvalidArgumentException;
use TBela\CSS\Value;

class TokenSelectorValueAttributeFunctionEndswith extends TokenSelectorValueAttributeFunctionGeneric
{
    protected $operator = '$=';
}