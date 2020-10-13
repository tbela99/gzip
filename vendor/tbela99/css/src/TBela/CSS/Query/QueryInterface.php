<?php

namespace TBela\CSS\Query;

interface QueryInterface
{

    /**
     * @param string $query
     * @return QueryInterface[]
     */
    public function query($query);
}