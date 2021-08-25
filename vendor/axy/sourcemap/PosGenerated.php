<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap;

/**
 * Position in the generated content
 *
 * @link https://github.com/axypro/sourcemap/blob/master/doc/PosMap.md documentation
 */
class PosGenerated
{
    /**
     * The line number (zero-based)
     *
     * @var int
     */
    public $line;

    /**
     * The column number
     *
     * @var int
     */
    public $column;
}
