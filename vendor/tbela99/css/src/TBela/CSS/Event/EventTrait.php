<?php

namespace TBela\CSS\Event;

trait EventTrait {

    /**
     * @var callable[]
     */
    protected $events = [];

    public function on($event, callable $callable) {

        $this->events[strtolower($event)][] = $callable;
        return $this;
    }

    public function off($event, callable $callable) {

        $event = strtolower($event);

        if (isset($this->events[$event])) {

            foreach ($this->events[$event] as $key => $value) {

                if ($value === $callable) {

                    array_splice($this->events[$event], $key, 1);
                    break;
                }
            }
        }

        return $this;
    }

    public function emit($event, ...$args) {

        $result = [];
        $event = strtolower($event);

        if (!isset($this->events[$event])) {

            return $result;
        }

        foreach ($this->events[$event] as $callable) {

            $result[] = call_user_func_array($callable, $args);
        }

        return $result;
    }
}