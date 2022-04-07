<?php

namespace TBela\CSS\Event;

trait EventTrait {

    /**
     * @var callable[][]
     * @ignore
     */
    protected $events = [];

    /**
     * register event handlers
     * @param string $event event name
     * @param callable $callable
     * @return $this
     */
    public function on($event, callable $callable) {

        $this->events[strtolower($event)][] = $callable;
        return $this;
    }

    /**
     * unregister events handlers.
     * - if $event is null, all events are removed
     * - if $callable is null, all events handlers for $event are removed
     *
     * @param string|null $event
     * @param callable|null $callable
     * @return $this
     */
    public function off($event = null, callable $callable = null) {

        if (is_null($event)) {

            $this->events = [];
        }
        else {

            $event = strtolower($event);

            if (is_null($callable)) {

                unset($this->events[$event]);
            }

            else if (isset($this->events[$event])) {

                foreach ($this->events[$event] as $key => $value) {

                    if ($value === $callable) {

                        array_splice($this->events[$event], $key, 1);
                        break;
                    }
                }
            }
        }


        return $this;
    }

    /**
     * trigger event
     * @param string $event
     * @param mixed ...$args
     * @return array
     */
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