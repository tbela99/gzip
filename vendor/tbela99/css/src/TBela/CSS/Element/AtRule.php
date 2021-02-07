<?php 

namespace TBela\CSS\Element;

use Exception;
use \TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\ElementTrait;

/**
 * Class AtRule
 * @package TBela\CSS\Element
 */
class AtRule extends RuleSet {

    use ElementTrait;

    /**
     * Type of @at-rule that contains other rules like @media
     */
    const ELEMENT_AT_RULE_LIST = 0;
    /**
     * Type of @at-rule that contains declarations @viewport
     */
    const ELEMENT_AT_DECLARATIONS_LIST = 1;
    /**
     * Type of @at-rule that contains no child like @namespace
     */
    const ELEMENT_AT_NO_LIST = 2;

    /**
     * test if this at-rule node is a leaf
     * @return bool
     */
    public function isLeaf () {

        return !empty($this->ast->isLeaf);
    }

    /**
     * test if this at-rule node contains declaration
     * @return bool
     */
    public function hasDeclarations () {

        return !empty($this->ast->hasDeclarations);
    }

    /**
     * @inheritDoc
     */
    public function support (ElementInterface $child) {

        if (!empty($this->ast->isLeaf)) {

            return false;
        }

        if ($child instanceof Comment) {

            return true;
        }

        if (!empty($this->ast->hasDeclarations)) {

            if (!($child instanceof Declaration)) {

                return false;
            }
        }

        else {

            if ($child instanceof Declaration) {

                return false;
            }
        }

        return parent::support($child);
    }

    /**
     * add a css declaration child node
     * @param string $name
     * @param string $value
     * @return Declaration
     * @throws Exception
     */
    public function addDeclaration ($name, $value) {

        $declaration = new Declaration();

        $declaration['name'] = $name;
        $declaration['value'] = $value;

        return $this->append($declaration);
    }

    /**
     * @return \stdClass
     * @ignore
     */
    public function jsonSerialize () {

        $ast = parent::jsonSerialize();

        if (empty($ast->isLeaf)) {

            unset($ast->isLeaf);
        }

        if (empty($ast->hasDeclarations)) {

            unset($ast->hasDeclarations);
        }

        return $ast;
    }
}