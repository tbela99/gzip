<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Parser\SyntaxError;

class Declaration implements ValidatorInterface
{
    use ValidatorTrait;

    public function validate($token, $parentRule, $parentStylesheet)
    {

        $this->error = null;

        if (!(in_array($parentRule->type, ['Rule', 'NestingRule', 'NestingAtRule']) ||
            in_array($parentStylesheet->type, ['Rule', 'NestingRule', 'NestingAtRule']) ||
            ($parentRule->type == 'AtRule' && !empty($parentRule->hasDeclarations)) ||
            ($parentStylesheet->type == 'AtRule' && !empty($parentStylesheet->hasDeclarations)))) {

            $this->error = 'a declaration is no allowed here';

            return static::REJECT;
        }

        if ($parentRule->type == 'NestingRule') {

            $i =  isset($parentRule->children) ? count($parentRule->children) : 0;

            while ($i--) {

                if ($parentRule->children[$i]->type == 'Comment') {

                    continue;
                }

                if ($parentRule->children[$i]->type != 'Declaration') {

                    $this->error = 'a declaration is no allowed here';
                    return static::REJECT;
                }

                break;
            }
        }

        return static::VALID;
    }
}