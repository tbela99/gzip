<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;

class InvalidAtRule implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {

        $this->error = 'malformed at-rule';

        return static::REJECT;
    }
}