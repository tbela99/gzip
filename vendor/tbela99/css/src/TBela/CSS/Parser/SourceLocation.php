<?php

namespace TBela\CSS\Parser;

use JsonSerializable;

/**
 * Class Location
 * @package TBela\CSS\Parser
 *
 * @property Position $start
 * @property Position $end
 */

class SourceLocation implements JsonSerializable {

    use AccessTrait;

    protected $start;
    protected $end;

    public function __construct($start, $end) {

        assert($start instanceof Position);
        assert($end instanceof Position);

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @param $location
     * @return SourceLocation
     */
    public static function getInstance($location)
    {

        return new static(Position::getInstance($location->start), Position::getInstance($location->end));
    }

    /**
     * @return Position
     */
    public function getStart() {

        return $this->start;
    }

    /**
     * @return Position
     */
    public function getEnd() {

        return $this->end;
    }

    /**
     * @param Position $start
     * @return SourceLocation
     */
    public function setStart($start) {

        assert($start instanceof Position);

        $this->start = $start;
        return $this;
    }

    /**
     * @param Position $end
     * @return SourceLocation
     */
    public function setEnd($end) {

        assert($end instanceof Position);

        $this->end = $end;
        return $this;
    }
}