<?php

namespace TBela\CSS\Event;

interface EventInterface {

    public function on($event, callable $callable);

    /**
     * @param string|null $event
     * @param callable|null $callable
     * @return $this
     */
    public function off($event = null, callable $callable = null);

    public function emit($event, ...$args);
}