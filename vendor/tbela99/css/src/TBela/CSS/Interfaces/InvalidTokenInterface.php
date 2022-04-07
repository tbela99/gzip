<?php

namespace TBela\CSS\Interfaces;
use TBela\CSS\Value;
use TBela\CSS\Value\Set;

/**
 * Interface implemented by Elements
 */
interface InvalidTokenInterface {

    /**
     * attempt to return a valid token
     * @param string|Value|null|Set $property
     * @return Value
     */
    public function recover($property = null);
}