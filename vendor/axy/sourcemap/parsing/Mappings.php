<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parsing;

use axy\sourcemap\PosMap;

/**
 * The "mappings" section
 */
class Mappings
{
    /**
     * The constructor
     *
     * @param string $sMappings
     *        the string from the JSON data
     * @param \axy\sourcemap\parsing\Context $context
     *        the parsing context
     */
    public function __construct($sMappings, Context $context)
    {
        $this->sMappings = $sMappings;
        $this->context = $context;
        $this->parse();
    }

    /**
     * Returns the list of lines
     *
     * @return \axy\sourcemap\parsing\Line[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Adds a position to the mappings
     *
     * @param \axy\sourcemap\PosMap
     */
    public function addPosition(PosMap $position)
    {
        $generated = $position->generated;
        $nl = $generated->line;
        if (isset($this->lines[$nl])) {
            $this->lines[$nl]->addPosition($position);
        } else {
            $this->lines[$nl] = new Line($nl, [$generated->column => $position]);
        }
        $this->sMappings = null;
    }

    /**
     * Removes a position
     *
     * @param int $line
     *        the generated line number
     * @param int $column
     *        the generated column number
     * @return bool
     *         the position was found and removed
     */
    public function removePosition($line, $column)
    {
        $removed = false;
        if (isset($this->lines[$line])) {
            $l = $this->lines[$line];
            $removed = $l->removePosition($column);
            if ($removed && $l->isEmpty()) {
                unset($this->lines[$line]);
            }
        }
        if ($removed) {
            $this->sMappings = null;
        }
        return $removed;
    }

    /**
     * Finds a position in the source files
     *
     * @param int $fileIndex
     * @param int $line
     * @param int $column
     * @return \axy\sourcemap\PosMap|null
     *         A position map or NULL if it is not found
     */
    public function findPositionInSource($fileIndex, $line, $column)
    {
        foreach ($this->lines as $oLine) {
            $pos = $oLine->findPositionInSource($fileIndex, $line, $column);
            if ($pos !== null) {
                return $pos;
            }
        }
        return null;
    }

    /**
     * Renames a file name
     *
     * @param int $fileIndex
     * @param string $newFileName
     */
    public function renameFile($fileIndex, $newFileName)
    {
        foreach ($this->lines as $line) {
            $line->renameFile($fileIndex, $newFileName);
        }
    }

    /**
     * Renames a symbol name
     *
     * @param int $nameIndex
     * @param string $newName
     */
    public function renameName($nameIndex, $newName)
    {
        foreach ($this->lines as $line) {
            $line->renameName($nameIndex, $newName);
        }
    }

    /**
     * Removes a file
     *
     * @param int $fileIndex
     * @return bool
     */
    public function removeFile($fileIndex)
    {
        $removed = false;
        $lines = $this->lines;
        foreach ($lines as $ln => $line) {
            if ($line->removeFile($fileIndex)) {
                $removed = true;
                if ($line->isEmpty()) {
                    unset($this->lines[$ln]);
                }
            }
        }
        $this->sMappings = null;
        return $removed;
    }

    /**
     * Removes a name
     *
     * @param int $nameIndex
     * @return bool
     */
    public function removeName($nameIndex)
    {
        $removed = false;
        $lines = $this->lines;
        foreach ($lines as $line) {
            if ($line->removeName($nameIndex)) {
                $removed = true;
            }
        }
        $this->sMappings = null;
        return $removed;
    }

    /**
     * Packs the mappings
     *
     * @return string
     */
    public function pack()
    {
        $parser = new SegmentParser();
        if ($this->sMappings === null) {
            $ln = [];
            $max = max(array_keys($this->lines));
            for ($i = 0; $i <= $max; $i++) {
                if (isset($this->lines[$i])) {
                    $parser->nextLine($i);
                    $ln[] = $this->lines[$i]->pack($parser);
                } else {
                    $ln[] = '';
                }
            }
            $this->sMappings = implode(';', $ln);
        }
        return $this->sMappings;
    }

    /**
     * Returns a position map by a position in the generated source
     *
     * @param int $line
     *        zero-based line number in the generated source
     * @param int $column
     *        zero-bases column number is the line
     * @return \axy\sourcemap\PosMap|null
     *         A position map or NULL if it is not found
     */
    public function getPosition($line, $column)
    {
        if (!isset($this->lines[$line])) {
            return null;
        }
        return $this->lines[$line]->getPosition($column);
    }

    /**
     * Finds positions that match to a filter
     *
     * @param \axy\sourcemap\PosMap $filter [optional]
     *        the filter (if not specified then returns all positions)
     * @return \axy\sourcemap\PosMap[]
     */
    public function find(PosMap $filter = null)
    {
        $lines = $this->getLines();
        if ($filter !== null) {
            $generated = $filter->generated;
            if ($generated->line !== null) {
                $nl = $generated->line;
                if (isset($lines[$nl])) {
                    return $lines[$nl]->find($filter);
                } else {
                    return [];
                }
            }
        }
        $result = [];
        foreach ($lines as $line) {
            $result = array_merge($result, $line->find($filter));
        }
        return $result;
    }

    /**
     * Inserts a block in the generated content
     *
     * @param int $sLine
     * @param int $sColumn
     * @param int $eLine
     * @param int $eColumn
     */
    public function insertBlock($sLine, $sColumn, $eLine, $eColumn)
    {
        if ($sLine === $eLine) {
            if (isset($this->lines[$sLine])) {
                $this->lines[$sLine]->insertBlock($sColumn, $eColumn - $sColumn);
            }
        } else {
            $dLines = $eLine - $sLine;
            $shifts = [];
            foreach ($this->lines as $nl => $line) {
                if ($nl > $sLine) {
                    $newNL = $nl + $dLines;
                    $shifts[$newNL] = $line;
                    unset($this->lines[$nl]);
                    $line->setNum($newNL);
                }
            }
            if (!empty($shifts)) {
                $this->lines = array_replace($this->lines, $shifts);
            }
            if (isset($this->lines[$sLine])) {
                $line = $this->lines[$sLine];
                $newLine = $line->breakLine($sColumn, $eColumn - $sColumn, $eLine);
                if ($newLine !== null) {
                    $this->lines[$eLine] = $newLine;
                }
                if ($line->isEmpty()) {
                    unset($this->lines[$sLine]);
                }
            }
        }
        $this->sMappings = null;
    }

    /**
     * Removes a block from the generated content
     *
     * @param int $sLine
     * @param int $sColumn
     * @param int $eLine
     * @param int $eColumn
     */
    public function removeBlock($sLine, $sColumn, $eLine, $eColumn)
    {
        if ($sLine === $eLine) {
            if (isset($this->lines[$sLine])) {
                $line = $this->lines[$sLine];
                $line->removeBlock($sColumn, $eColumn);
                if ($line->isEmpty()) {
                    unset($this->lines[$sLine]);
                }
            }
        } else {
            $dLines = $eLine - $sLine;
            $shifts = [];
            $lineS = null;
            $lineE = null;
            if (isset($this->lines[$sLine])) {
                $lineS = $this->lines[$sLine];
                unset($this->lines[$sLine]);
            }
            if (isset($this->lines[$eLine])) {
                $lineE = $this->lines[$eLine];
                unset($this->lines[$eLine]);
            }
            foreach ($this->lines as $nl => $line) {
                if ($nl > $sLine) {
                    if ($nl > $eLine) {
                        $newNL = $nl - $dLines;
                        $shifts[$newNL] = $line;
                        $line->setNum($newNL);
                    }
                    unset($this->lines[$nl]);
                }
            }
            if (!empty($shifts)) {
                $this->lines = array_replace($this->lines, $shifts);
            }
            if ($lineS !== null) {
                $lineS->removeBlock($sColumn, 1000000000);
                if ($lineS->isEmpty()) {
                    $lineS = null;
                }
            }
            if ($lineE !== null) {
                $lineE->removeBlockBegin($eColumn);
                $lineE->setNum($sLine);
                if ($lineE->isEmpty()) {
                    $lineE = null;
                }
            }
            if ($lineS && $lineE) {
                $this->lines[$sLine] = $lineS;
                $lineS->addPositionsList($lineE->getPositions());
            } elseif ($lineS) {
                $this->lines[$sLine] = $lineS;
            } elseif ($lineE) {
                $this->lines[$sLine] = $lineE;
            }
        }
        $this->sMappings = null;
    }

    /**
     * Returns statistic by sources and names
     *
     * @return array
     */
    public function getStat()
    {
        $sources = [];
        $names = [];
        foreach ($this->lines as $line) {
            $line->loadStat($sources, $names);
        }
        return [
            'sources' => $sources,
            'names' => $names,
        ];
    }

    /**
     * @param \axy\sourcemap\parsing\Mappings $other
     * @param int $line
     * @param int $column
     * @param int[] $mSources
     * @param int[] $mNames
     */
    public function concat(Mappings $other, $line, $column, $mSources, $mNames)
    {
        foreach ($other->getLines() as $num => $oLine) {
            $l = $num + $line;
            $c = $num ? 0 : $column;
            $oLine->concat($l, $c, $mSources, $mNames);
            if (isset($this->lines[$l])) {
                $this->lines[$l]->addPositionsList($oLine->getPositions());
            } else {
                $this->lines[$l] = $oLine;
            }
        }
        $this->sMappings = null;
    }

    /**
     * @param \axy\sourcemap\parsing\Mappings $other
     * @param int $sIndex
     * @param int[] $mSources
     * @param int[] $mNames
     */
    public function merge(Mappings $other, $sIndex, $mSources, $mNames)
    {
        $mp = [];
        foreach ($other->getLines() as $ln => $line) {
            foreach ($line->getPositions() as $cn => $pos) {
                $s = $pos->source;
                $key = $ln.':'.$cn;
                if (!isset($mp[$key])) {
                    $mp[$key] = $s;
                }
            }
        }
        foreach ($this->getLines() as $ln => $line) {
            foreach ($line->getPositions() as $cn => $pos) {
                $s = $pos->source;
                if ($s->fileIndex !== $sIndex) {
                    continue;
                }
                $key = $s->line.':'.$s->column;
                if (!isset($mp[$key])) {
                    $line->removePosition($cn);
                    continue;
                }
                $os = $mp[$key];
                unset($mp[$key]);
                $pos->source = $os;
                if (($os->fileIndex !== null) && isset($mSources[$os->fileIndex])) {
                    $os->fileIndex = $mSources[$os->fileIndex];
                }
                if (($os->nameIndex !== null) && isset($mNames[$os->nameIndex])) {
                    $os->nameIndex = $mNames[$os->nameIndex];
                }
            }
            if ($line->isEmpty()) {
                unset($this->lines[$ln]);
            }
        }
        $this->sMappings = null;
    }

    public function __clone()
    {
        if ($this->lines !== null) {
            foreach ($this->lines as &$line) {
                $line = clone $line;
            }
            unset($line);
        }
    }

    /**
     * Parses the mappings string
     */
    private function parse()
    {
        $this->lines = [];
        $parser = new SegmentParser();
        foreach (explode(';', $this->sMappings) as $num => $sLine) {
            $sLine = trim($sLine);
            if ($sLine !== '') {
                $this->lines[$num] = Line::loadFromMappings($num, $sLine, $parser, $this->context);
            }
        }
    }

    /**
     * @var \axy\sourcemap\parsing\Line[]
     */
    private $lines;

    /**
     * @var string
     */
    private $sMappings;

    /**
     * @var \axy\sourcemap\parsing\Context
     */
    private $context;
}
