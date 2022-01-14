<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Value;

class NestingRule implements ValidatorInterface
{
    public function validate(object $token, object $parentRule, object $parentStylesheet): int
    {

       if (in_array($parentRule->type, ['NestingRule', 'NestingMediaRule'])) {

           foreach (Value::split($token->selector, ',') as $selector) {

               if (strpos($selector, '&') !== 0) {

                   return static::REJECT;
               }
           }
       }

        return static::VALID;
    }
}