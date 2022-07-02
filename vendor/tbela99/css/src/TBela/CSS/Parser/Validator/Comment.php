<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;

class Comment implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {

        $this->error = null;

        if (substr($token->value, 0, 4) == '<!--') {

            if ($parentRule->type !== 'Stylesheet') {

                $this->error = 'comment is allowed only in a stylesheet';
                return static::REJECT;
            }

            return static::REMOVE;
        }

        return substr($token->value, 0, 3) == '/*#' ? static::REMOVE : static::VALID;
    }
}