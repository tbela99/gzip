<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\helpers;

use axy\sourcemap\SourceMap;

/**
 * The builder of source map
 */
class MapBuilder
{
    /**
     * Builds a source map instance
     *
     * @param \axy\sourcemap\SourceMap|array|string $pointer
     *        a source map, an array of data or a file name
     * @return \axy\sourcemap\SourceMap
     * @throws \axy\sourcemap\errors\IOError
     * @throws \axy\sourcemap\errors\InvalidFormat
     * @throws \InvalidArgumentException
     */
    public static function build($pointer)
    {
        if (is_string($pointer)) {
            return SourceMap::loadFromFile($pointer);
        }
        if (is_array($pointer)) {
            return new SourceMap($pointer);
        }
        if ($pointer instanceof SourceMap) {
            return $pointer;
        }
        throw new \InvalidArgumentException('Argument must be a SourceMap or a data array or a file name');
    }
}
