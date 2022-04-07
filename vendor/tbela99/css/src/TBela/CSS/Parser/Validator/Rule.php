<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;
use TBela\CSS\Value;

class Rule implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {

        $this->error = null;

       if(in_array($parentRule->type, ['NestingAtRule', 'NestingRule', 'Rule']) ||
           in_array($parentStylesheet->type, ['NestingAtRule', 'NestingRule', 'Rule'])) {

           foreach (Value::split($token->selector, ',') as $selector) {

               if (strpos(trim($selector), '&') !== 0) {

                   $this->error = new SyntaxError('the selector must start with "&"');
                   return static::REJECT;
               }
           }
       }

        return static::VALID;
    }
}