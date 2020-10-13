<?php 

namespace TBela\CSS\Interfaces;

use Exception;
use IteratorAggregate;
use TBela\CSS\Element;

/**
 * Interface implemented by rules containers
 * @package TBela\CSS
 * @property-read array childNodes. Return the child nodes. Accessed with array-like syntax $element['childNodes']
 * @property-read Element|null firstChild. Return the first child. Accessed with array-like syntax $element['firstChild']
 * @property-read Element|null lastChild. Return the last child. Accessed with array-like syntax $element['lastChild']
 */
interface RuleListInterface extends IteratorAggregate {

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
     * @param Element[] $elements
     * @return Element
     * @throws Exception
     */
    public function setChildren(array $elements);

    /**
     * Add a comment node
     * @param string $value
     * @return Element\Comment
     * @throws Exception
     */
    public function addComment ($value);

    /**
     * check if this node accepts element as a child
     * @param Element $child
     * @return bool
     */
    public function support (Element $child);

    /**
     * append child nodes
     * @param Element|Element[] $elements
     * @return Element
     * @throws Exception
     */
    public function append(Element ...$elements);

    /**
     * append css text to this node
     * @param string $css
     * @return Element
     * @throws Exception
     */
    public function appendCss($css);

    /**
     * insert a child node at the specified position
     * @param Element $element
     * @param int $position
     * @return Element
     * @throws Exception
     */
    public function insert(Element $element, $position);

    /**
     * remove a child node from its parent
     * @param Element $element
     * @return Element
     */
    public function remove (Element $element);

    /**
     * Remove all children
     * @return Element
     */
    public function removeChildren();

    /**
     * @return RuleListInterface
     */
    public function computeShortHand();
}