<?php

namespace TBela\CSS\Query;

trait TokenStringifiableTrait
{
    public function __toString() {

        return $this->render();
    }
}