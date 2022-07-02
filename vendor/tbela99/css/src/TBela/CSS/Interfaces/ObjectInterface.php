<?php 

namespace TBela\CSS\Interfaces;

/**
 * Interface Renderable
 * @package TBela\CSS
 * @method getName(): string;
 * @method getType(): string;
 * @method getValue(): stringt;
 * @method getRawValue(): ?array;
 */
interface ObjectInterface {

    /**
     * convert to object
     * @return mixed
     */
    public function toObject();
}