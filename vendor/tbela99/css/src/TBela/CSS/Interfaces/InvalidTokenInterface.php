<?php

namespace TBela\CSS\Interfaces;

/**
 * Interface implemented by Elements
 */
interface InvalidTokenInterface {

    /**
     * recover an invalid token
     */
    public static function doRecover(object $data): object;
}