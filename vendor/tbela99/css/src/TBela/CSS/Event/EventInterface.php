<?php

namespace TBela\CSS\Event;

interface EventInterface {

    public function on($event, callable $callable);

    public function off($event, callable $callable);

    public function emit($event, ...$args);
}