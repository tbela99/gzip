<?php

namespace TBela\CSS\Query;

use TBela\CSS\Interfaces\ObjectInterface;
use TBela\CSS\Interfaces\RenderableInterface;

interface QueryInterface extends RenderableInterface
{

    /**
     * @param string $query
     * @return QueryInterface[]
     */
    public function query($query);
}