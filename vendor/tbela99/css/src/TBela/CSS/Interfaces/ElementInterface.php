<?php

namespace TBela\CSS\Interfaces;
use JsonSerializable;
use ArrayAccess;
use TBela\CSS\Parser\SourceLocation;
use TBela\CSS\Query\QueryInterface;
use TBela\CSS\RuleList;
use TBela\CSS\Value;

/**
 * Interface implemented by Elements
 */
interface ElementInterface extends QueryInterface, JsonSerializable, ArrayAccess {

    /**
     * create an instance from ast or another Element instance
     * @param ElementInterface|object $ast
     * @return mixed
     */
    public static function getInstance($ast);

    /**
     * @param callable $fn
     * @param string $event
     * @return ElementInterface
     */
    public function traverse(callable $fn, $event);

    /**
     * search nodes using query selector syntax
     * @param string $query
     * @return array
     * @throws \TBela\CSS\Parser\SyntaxError
     */

    public function query($query);

    /**
     * search nodes by class names
     * @param string $query comma separated list of class names
     * @return array
     * @throws \TBela\CSS\Parser\SyntaxError
     */
    public function queryByClassNames($query);

    /**
     * return the root element
     * @return ElementInterface
     */
    public function getRoot ();

    /**
     * return Value\Set|string
     * @return string
     */
    public function getValue();

    /**
     * assign the value
     * @param Value\Set|string $value
     * @return $this
     */
    public function setValue ($value);

    /**
     * get the parent node
     * @return RuleList|null
     */
    public function getParent ();

    /**
     * return the type
     * @return string
     */
    public function getType();

    /**
     * Clone parents, children and the element itself. Useful when you want to render this element only and its parents.
     * @return ElementInterface
     */
    public function copy();

    /**
     * @param SourceLocation|null $location
     * @return ElementInterface
     */
    public function setLocation($location);

    /**
     * @return SourceLocation|null
     */
    public function getLocation();

    /**
     * merge css rules and declarations
     * @param array $options
     * @return ElementInterface
     */
    public function deduplicate(array $options = ['allow_duplicate_rules' => ['font-face']]);

    /**
     * convert to string
     * @return string
     * @throws Exception
     */
    public function __toString();

    /**
     * clone object
     * @ignore
     */
    public function __clone();
}