<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\tests;

use axy\sourcemap\SourceMap;
use axy\sourcemap\PosMap;
use axy\sourcemap\parsing\Line;
use axy\sourcemap\parsing\Mappings;

/**
 * Representation of the library objects as arrays for tests
 */
class Represent
{
    /**
     * @param \axy\sourcemap\PosMap $pos
     * @return array
     */
    public static function posMap(PosMap $pos)
    {
        return [
            'generated' => (array)$pos->generated,
            'source' => (array)$pos->source,
        ];
    }

    /**
     * @param \axy\sourcemap\parsing\Line $line
     * @return array
     */
    public static function line(Line $line)
    {
        $result = [];
        foreach ($line->getPositions() as $column => $position) {
            $result[$column] = self::posMap($position);
        }
        return $result;
    }

    /**
     * @param \axy\sourcemap\parsing\Mappings $mappings
     * @return array
     */
    public static function mappings(Mappings $mappings)
    {
        $result = [];
        foreach ($mappings->getLines() as $num => $line) {
            $result[$num] = self::line($line);
        }
        return $result;
    }

    /**
     * @param \axy\sourcemap\SourceMap $map
     * @return array
     */
    public static function shortMap(SourceMap $map)
    {
        $result = [];
        foreach ($map->find() as $p) {
            $g = $p->generated;
            $s = $p->source;
            $line = $g->line;
            if (!isset($result[$line])) {
                $result[$line] = [];
            }
            $result[$line][$g->column] = implode(',', [$s->fileIndex, $s->line, $s->column, $s->nameIndex]);
        }
        return $result;
    }
}
