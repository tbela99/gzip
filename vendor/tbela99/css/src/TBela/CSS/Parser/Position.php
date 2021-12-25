<?php

namespace TBela\CSS\Parser;

use JsonSerializable;

/**
 * Class Position
 * @package TBela\CSS\Parser
 *
 * @property int $line
 * @property int $column
 * @property int $index
 */
class Position implements JsonSerializable  {

    use AccessTrait;

    protected int $line;
    protected int $column;
    protected int $index;

    public function __construct(int $line, int $column, int $index) {

        $this->line = $line;
        $this->column = $column;
        $this->index = $index;
    }

    public static function getInstance($start)
    {

        return new static($start->line, $start->column, $start->index);
    }

    public function getLine(): int {

        return $this->line;
    }

    public function getColumn(): int {

        return $this->column;
    }

    public function getIndex(): int {

        return $this->index;
    }

    public function setLine(int $line): Position {

        $this->line = $line;
        return $this;
    }

    public function setColumn(int $column): Position {

        $this->column = $column;
        return $this;
    }

    public function setIndex(int $index): Position {

        $this->index = $index;
        return $this;
    }
}