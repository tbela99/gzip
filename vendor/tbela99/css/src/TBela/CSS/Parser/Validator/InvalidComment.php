<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;

class InvalidComment implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {
        $this->error = 'malformed comment or comment not allowed here';

        return static::REJECT;
    }
}