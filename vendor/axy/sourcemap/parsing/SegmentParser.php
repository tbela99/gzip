<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parsing;

use axy\sourcemap\PosMap;
use axy\sourcemap\PosGenerated;
use axy\sourcemap\PosSource;
use axy\sourcemap\errors\InvalidMappings;
use axy\codecs\base64vlq\Encoder;
use axy\codecs\base64vlq\errors\Error as VLQError;

/**
 * Mappings segment parser
 */
class SegmentParser
{
    /**
     * The constructor
     */
    public function __construct()
    {
        $this->encoder = Encoder::getStandardInstance();
    }

    /**
     * Notification of the beginning of a new line
     *
     * @param int $num [optional]
     *        the line number
     */
    public function nextLine($num)
    {
        $this->gLine = $num;
        $this->gColumn = 0;
    }

    /**
     * Parses a segment
     *
     * @param string $segment
     *        a segment from the "mappings" section
     * @return \axy\sourcemap\PosMap
     * @throws \axy\sourcemap\errors\InvalidMappings
     */
    public function parse($segment)
    {
        $generated = new PosGenerated();
        $source = new PosSource();
        $pos = new PosMap($generated, $source);
        try {
            $offsets = $this->encoder->decode($segment);
        } catch (VLQError $e) {
            throw new InvalidMappings('Invalid segment "'.$segment.'"', $e);
        }
        $count = count($offsets);
        if (!in_array($count, [1, 4, 5])) {
            throw new InvalidMappings('Invalid segment "'.$segment.'"');
        }
        $generated->line = $this->gLine;
        $this->gColumn += $offsets[0];
        $generated->column = $this->gColumn;
        if ($count >= 4) {
            $this->sFile += $offsets[1];
            $this->sLine += $offsets[2];
            $this->sColumn += $offsets[3];
            $source->fileIndex = $this->sFile;
            $source->line = $this->sLine;
            $source->column = $this->sColumn;
            if ($count === 5) {
                $this->sName += $offsets[4];
                $source->nameIndex = $this->sName;
            }
        }
        return $pos;
    }

    /**
     * Packs a position to the mappings segment
     *
     * @param \axy\sourcemap\PosMap $pos
     * @return string
     */
    public function pack(PosMap $pos)
    {
        $generated = $pos->generated;
        $source = $pos->source;
        $offsets = [$generated->column - $this->gColumn];
        $this->gColumn = $generated->column;
        if ($source->fileIndex !== null) {
            $offsets[] = $source->fileIndex - $this->sFile;
            $offsets[] = $source->line - $this->sLine;
            $offsets[] = $source->column - $this->sColumn;
            $this->sFile = $source->fileIndex;
            $this->sLine = $source->line;
            $this->sColumn = $source->column;
            if ($source->nameIndex !== null) {
                $offsets[] = $source->nameIndex - $this->sName;
                $this->sName = $source->nameIndex;
            }
        }
        return $this->encoder->encode($offsets);
    }

    /**
     * The line number of the generated content (zero-based)
     *
     * @var int
     */
    private $gLine = 0;

    /**
     * The column number of the generated content (zero-based)
     *
     * @var int
     */
    private $gColumn;

    /**
     * The file index from the "sources" section
     *
     * @var int
     */
    private $sFile;

    /**
     * The line number of the current source file (zero-based)
     *
     * @var int
     */
    private $sLine;

    /**
     * The column number of the current source file (zero-based)
     *
     * @var int
     */
    private $sColumn;

    /**
     * The name index from the "names" section
     *
     * @var int
     */
    private $sName;

    /**
     * @var \axy\codecs\base64vlq\Encoder
     */
    private $encoder;
}
