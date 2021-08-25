<?php

namespace TBela\CSS\Interfaces;

interface ParsableInterface {

    /**
     * return the ast
     * @return \stdClass|null
     */
    public function getAst();
}