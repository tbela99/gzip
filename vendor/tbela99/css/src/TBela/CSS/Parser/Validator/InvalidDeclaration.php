<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;

class InvalidDeclaration implements ValidatorInterface
{
    public function validate($token, $parentRule, $parentStylesheet)
    {

        return static::REJECT;
    }
}