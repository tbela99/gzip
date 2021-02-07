<?php 

namespace TBela\CSS\Element ;

use Exception;
use InvalidArgumentException;
use TBela\CSS\RuleList;

/**
 * Rules container
 * @package TBela\CSS
 */
abstract class RuleSet extends RuleList {

    /**
     * Add at-rule child node
     * @param string $name
     * @param string|null $value
     * @param int $type the type of the node:
     * - AtRule::ELEMENT_AT_RULE_LIST (the elements can contain other rules)
     * - AtRule::ELEMENT_AT_DECLARATIONS_LIST the element contains declarations
     * - AtRule::ELEMENT_AT_NO_LIST the element does not support children
     * @return AtRule
     * @throws Exception
     */
    public function addAtRule($name, $value = null, $type = 0) {

        $rule = new AtRule();

        if ($type < 0 || $type > 2) {

            throw new InvalidArgumentException('Illegal rule type: '.$type);
        }

        switch ($type) {

            case AtRule::ELEMENT_AT_RULE_LIST:

                break;
            case AtRule::ELEMENT_AT_DECLARATIONS_LIST:

                $rule->ast->hasDeclarations = true;
                break;

            case AtRule::ELEMENT_AT_NO_LIST:

                $rule->ast->isLeaf = true;
                break;
        }

        $rule->setName($name);
        $rule->setValue($value);

        return $this->append($rule);
    }

    /**
     * add a rule child node
     * @param string|array $selectors
     * @return Rule
     * @throws Exception
     */
    public function addRule ($selectors) {

        $rule = new Rule();
        $rule->setSelector($selectors);

        return $this->append($rule);
    }
}