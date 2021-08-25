<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\helpers;

use axy\sourcemap\PosMap;

/**
 * Builds a position map instance by parameters
 */
class PosBuilder
{
    /**
     * Builds a position map
     *
     * @param \axy\sourcemap\PosMap|array|object $p
     * @return \axy\sourcemap\PosMap
     */
    public static function build($p)
    {
        if (is_object($p)) {
            if ($p instanceof PosMap) {
                return $p;
            }
            $generated = isset($p->generated) ? $p->generated : null;
            $source = isset($p->source) ? $p->source : null;
        } elseif (is_array($p)) {
            $generated = isset($p['generated']) ? $p['generated'] : null;
            $source = isset($p['source']) ? $p['source'] : null;
        } else {
            $generated = null;
            $source = null;
        }
        return new PosMap($generated, $source);
    }
}
