<?php

namespace TBela\CSS\Element;

use \TBela\CSS\Interfaces\ElementInterface;

class NestingRule extends Rule
{

    /**
     * @inheritDoc
     */
    public function support(ElementInterface $child)
    {

        return true;
    }
}