<?php 

namespace TBela\CSS\Interfaces;

/**
 * Interface Renderable
 * @package TBela\CSS
 * @method getName(bool $vendor = true): string;
 * @method getType(): string;
 * @method getValue(): \TBela\CSS\Value\Set|string;
 */
interface RenderableInterface extends ParsableInterface, ObjectInterface {

    /**
     * @param array|null $comments
     * @return RenderableInterface
     */
    public function setTrailingComments(array $comments = null);

    /**
     * @return string[]|null
     */
    public function getTrailingComments();

    /**
     * @param string[]|null $comments
     * @return ObjectInterface
     */
    public function setLeadingComments(array $comments = null);

    /**
     * @return string[]|null
     */
    public function getLeadingComments();
}