<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;
use TBela\CSS\Value;

class NestingAtRule implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {

        $this->error = null;

       if(!in_array($parentRule->type, ['Rule', 'NestingRule', 'NestingAtRule', 'NestingMediaRule']) &&
           !in_array($parentStylesheet->type, ['Rule', 'NestingRule', 'NestingAtRule', 'NestingMediaRule'])) {

           $this->error = new SyntaxError(sprintf('%s is not valid here', $token->type));
           return static::REJECT;
       }

        foreach (Value::split($token->selector, ',') as $selector) {

            if (strpos($selector, '&') === false) {

                $this->error = new SyntaxError('the selector must contain "&');
                return static::REJECT;
            }
        }

        return static::VALID;
    }
}