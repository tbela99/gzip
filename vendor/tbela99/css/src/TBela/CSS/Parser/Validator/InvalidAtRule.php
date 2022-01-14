<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;

class InvalidAtRule implements ValidatorInterface
{
    public function validate($token, $parentRule, $parentStylesheet)
    {

        return static::REJECT;
    }
}