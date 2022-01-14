<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Value;

class Rule implements ValidatorInterface
{
    public function validate($token, $parentRule, $parentStylesheet)
    {

       if(in_array($parentRule->type, ['NestingAtRule', 'NestingRule', 'Rule']) ||
           in_array($parentStylesheet->type, ['NestingAtRule', 'NestingRule', 'Rule'])) {

           foreach (Value::split($token->selector, ',') as $selector) {

               if (strpos(trim($selector), '&') !== 0) {

                   return static::REJECT;
               }
           }
       }

        return static::VALID;
    }
}