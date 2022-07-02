<?php

namespace TBela\CSS\Parser\Validator;

use TBela\CSS\Parser\SyntaxError;

trait ValidatorTrait
{

    protected $error = null;

    public function getError() {

        return $this->error;
    }
}