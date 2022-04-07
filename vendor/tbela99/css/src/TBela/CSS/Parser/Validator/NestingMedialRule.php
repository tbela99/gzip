<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Value;

class NestingMedialRule implements ValidatorInterface
{
    use  ValidatorTrait;
    
    public function validate($token, $parentRule, $parentStylesheet)
    {

        return static::VALID;
    }
}