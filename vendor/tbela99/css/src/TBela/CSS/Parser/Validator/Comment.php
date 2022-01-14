<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;

class Comment implements ValidatorInterface
{
    public function validate($token, $parentRule, $parentStylesheet)
    {

        if (substr($token->value, 0, 4) == '<!--') {

            if ($parentRule->type !== 'Stylesheet') {

                return static::REJECT;
            }

            return static::REMOVE;
        }

        return substr($token->value, 0, 3) == '/*#' ? static::REMOVE : static::VALID;
    }
}