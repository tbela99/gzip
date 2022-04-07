<?php

namespace TBela\CSS\Interfaces;

use TBela\CSS\Parser\SyntaxError;

interface ValidatorInterface
{

    /**
     * the token is valid
     */
    const VALID = 1;

    /**
     * the token must be removed
     */
    const REMOVE = 2;

    /**
     * reject is parse error for unexpected | invalid token
     */
    const REJECT = 3;

    /**
     * @param object $token
     * @param object $parentRule
     * @param object|null $parentStylesheet
     * @return int
     */
    public function validate($token, $parentRule, $parentStylesheet);
    public function getError();
}