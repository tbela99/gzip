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

    protected $line;
    protected $column;
    protected $index;

    public function __construct($line, $column, $index) {

        $this->line = $line;
        $this->column = $column;
        $this->index = $index;
    }

    public static function getInstance($start)
    {

        return new static($start->line, $start->column, $start->index);
    }

    public function getLine() {

        return $this->line;
    }

    public function getColumn() {

        return $this->column;
    }

    public function getIndex() {

        return $this->index;
    }

    public function setLine($line) {

        $this->line = $line;
        return $this;
    }

    public function setColumn($column) {

        $this->column = $column;
        return $this;
    }

    public function setIndex($index) {

        $this->index = $index;
        return $this;
    }
}