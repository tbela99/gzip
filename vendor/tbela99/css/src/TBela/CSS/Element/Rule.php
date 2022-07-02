<?php

namespace TBela\CSS\Element;

use Exception;
use \TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Value;

class Rule extends RuleList
{

    /**
     * Return the css selectors
     * @return string[]
     */
    public function getSelector()
    {

        return $this->ast->selector;
    }

    protected function parseSelector($selectors)
    {

        if (is_array($selectors) && is_string($selectors[0])) {

            $selectors = implode(',', $selectors);
        }

        if (is_string($selectors)) {

            $selectors = Value::parse($selectors, null, true, '', '');
        }

//        $comments = [];
//        $k = count($selectors);

//        while ($k--) {
//
//            if ($selectors[$k]->type == 'Comment') {
//
//                $comments[] = $selectors[$k]->value;
//                array_splice($selectors, $k, 1);
//            }
//        }
//
//        if (!empty($comments)) {
//
//            $this->setLeadingComments(array_reverse($comments));
//        }

        $selectors = Value::split(Value::renderTokens($selectors, ['omit_unit' => false]), ',');

        $result = [];

        foreach ($selectors as $selector) {

            $selector = trim($selector);
            $result[$selector] = $selector;
        }

        return array_values($result);
    }

    /**
     * Set css rule selector
     * @param string|array $selectors
     * @return $this
     */
    public function setSelector($selectors)
    {

        $this->ast->selector = $this->parseSelector($selectors);
        return $this;
    }

    /**
     * Add css selectors
     * @param array|string $selector
     * @return $this
     */
    public function addSelector($selector)
    {

        $result = [];

        foreach ($this->ast->selector as $r) {

            $result[trim($r)] = $r;
        }

        foreach ($this->parseSelector($selector) as $r) {

            $result[trim($r)] = $r;
        }

        $this->ast->selector = array_values($result);
        return $this;
    }

    /**
     * Remove a css selector
     * @param array|string $selector
     * @return Rule
     */
    public function removeSelector($selector)
    {

        if (!is_array($selector)) {

            $selector = array_map('trim', explode(',', $selector));
        }

        $this->ast->selector = array_diff($this->ast->selector, $selector);
        return $this;
    }

    /**
     * Add css declaration
     * @param string $name
     * @param string $value
     * @return Declaration
     * @throws Exception
     */
    public function addDeclaration($name, $value)
    {

        $declaration = new Declaration();

        $declaration['name'] = $name;
        $declaration['value'] = $value;

        return $this->append($declaration);
    }

    /**
     * Merge another css rule into this
     * @param Rule $rule
     * @return Rule $this
     * @throws Exception
     */
    public function merge(Rule $rule)
    {

        $this->addSelector($rule->getSelector());

        foreach ($rule->getChildren() as $element) {

            $this->addDeclaration($element->getName(), $element->getValue());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function support(ElementInterface $child)
    {

        if ($child instanceof Comment) {

            return true;
        }

        return $child instanceof Declaration;
    }
}