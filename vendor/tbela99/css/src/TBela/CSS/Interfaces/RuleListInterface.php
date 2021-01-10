<?php

namespace TBela\CSS\Interfaces;

use Exception;
use IteratorAggregate;
//use TBela\CSS\Element;

/**
 * Interface implemented by rules containers
 * @package TBela\CSS
 * @property-read array childNodes. Return the child nodes. Accessed with array-like syntax $element['childNodes']
 * @property-read ElementInterface|null firstChild. Return the first child. Accessed with array-like syntax $element['firstChild']
 * @property-read ElementInterface|null lastChild. Return the last child. Accessed with array-like syntax $element['lastChild']
 */
interface RuleListInterface extends ElementInterface, IteratorAggregate {

    /**
     * return true if the node has children
     * @return bool
     */
    public function hasChildren();
    /**
     * return child nodes
     * @return array
     */
    public function getChildren();

    /**
     * append child node
     * @param ElementInterface[] $elements
     * @return ElementInterface
     * @throws Exception
     */
    public function setChildren(array $elements);

    /**
     * Add a comment node
     * @param string $value
     * @return Element\Comment
     * @throws Exception
     */
    public function addComment($value);

    /**
     * check if this node accepts element as a child
     * @param ElementInterface $child
     * @return bool
     */
    public function support(ElementInterface $child);

    /**
     * append child nodes
     * @param ElementInterface|ElementInterface[] $elements
     * @return ElementInterface
     * @throws Exception
     */
    public function append(ElementInterface ...$elements);

    /**
     * append css text to this node
     * @param string $css
     * @return ElementInterface
     * @throws Exception
     */
    public function appendCss($css);

    /**
     * insert a child node at the specified position
     * @param ElementInterface $element
     * @param int $position
     * @return ElementInterface
     * @throws Exception
     */
    public function insert(ElementInterface $element, $position);

    /**
     * remove a child node from its parent
     * @param ElementInterface $element
     * @return ElementInterface
     */
    public function remove(ElementInterface $element);

    /**
     * Remove all children
     * @return ElementInterface
     */
    public function removeChildren();

    /**
     * @return RuleListInterface
     */
    public function computeShortHand();
}