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

        if (is_array($selectors)) {

            if (empty(array_filter($selectors, function ($value) {

                return !($value instanceof Value);
            }))) {

                return $selectors;
            }

            $selectors = implode(',', array_map(function ($selector) {

                $result = $selector->render();

                if ($selector->type == 'unit' && $selector->value == '0') {

                    $result .= $selector->unit;
                }

                return $result;

            }, $selectors));
        }

        $selectors = Value::parse($selectors);

        $comments = [];
        $selectors->filter(function ($node) use (&$comments) {

            if ($node->type == 'Comment') {

                $comments[] = $node;
                return false;
            }

            return true;
        });

        if (!empty($comments)) {

            $this->setLeadingComments($comments);
        }

        $selectors = $selectors->split(',');

        $result = [];

        foreach ($selectors as $selector) {

            $result[trim($selector)] = $selector;
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

        $this->ast->selector[] = array_values($result);
        return $this;
    }

    /**
     * Remove a css selector
     * @param array|string $selector
     * @return $this
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