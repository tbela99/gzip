<?php 

namespace TBela\CSS\Element;

use \TBela\CSS\Interfaces\ElementInterface;

/**
 * Class AtRule
 * @package TBela\CSS\Element
 */
class NestingMediaRule extends AtRule {

    public function getName(bool $getVendor = true): string
    {

        return 'media';
    }

    /**
     * test if this at-rule node is a leaf
     * @return bool
     */
    public function isLeaf () {

        return false;
    }

    /**
     * test if this at-rule node contains declaration
     * @return bool
     */
    public function hasDeclarations () {

        return true;
    }

    /**
     * @inheritDoc
     */
    public function support (ElementInterface $child) {

        return true;
    }
}