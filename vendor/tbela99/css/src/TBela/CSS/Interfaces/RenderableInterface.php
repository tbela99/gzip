<?php 

namespace TBela\CSS\Interfaces;

/**
 * Interface Renderable
 * @package TBela\CSS
 * @method getName(): string;
 * @method getType(): string;
 * @method getValue(): \TBela\CSS\Value\Set;
 */
interface RenderableInterface {

    /**
     * @param array|null $comments
     * @return RenderableInterface
     */
    public function setTrailingComments($comments);

    /**
     * @return string[]|null
     */
    public function getTrailingComments();

    /**
     * @param string[]|null $comments
     * @return RenderableInterface
     */
    public function setLeadingComments($comments);

    /**
     * @return string[]|null
     */
    public function getLeadingComments();
}