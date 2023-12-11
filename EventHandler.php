<?php

declare(strict_types=1);

namespace MaplePHP\Container;

use MaplePHP\Container\Interfaces\EventInterface;
use MaplePHP\Container\Reflection;
use BadMethodCallException;

/**
 * Create an event handler that will listent to a certain method in class
 */
class EventHandler
{
    private $handler;
    private $event;
    private $bindable = [];
    private $stopPropagate = false;

    /**
     * Add a class handler that you want to listen to
     * @param object|string $handler
     * @return void
     */
    public function addHandler(object|string $handler, string|array $method = null): void
    {
        if (!is_array($method)) {
            $method = [$method];
        }

        if (is_object($handler)) {
            $this->handler[] = [$handler, $method];
        } else {
            $reflect = new Reflection($handler);
            $this->handler[] = [$reflect->get(), $method];
        }
    }

    /**
     * Attach an event to a method that exist in handler
     * @param string   $method
     * @param callable $event
     * @return void
     */
    public function addEvent(callable|object|string $event, ?string $bind = null): void
    {
        if(is_string($event)) {
            $reflect = new Reflection($event);
            $event = $reflect->get();
        }

        if(is_object($event) && !($event instanceof EventInterface)) {
            throw new \Exception("Event object/class needs to be instance of \"EventInterface\"!", 1);
        }

        if (is_null($bind)) {
            $this->event[] = $event;
        } else {
            $this->event[] = [$bind => $event];
        }
    }

    /**
     * Make sure event only is executed once!
     * @param  bool   $stopPropagate
     * @return void
     */
    public function stopPropagation(bool $stopPropagate): void 
    {
        $this->stopPropagate = $stopPropagate;
    }


    /**
     * Release the listener
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        $data = null;
        foreach ($this->handler as $handler) {
            /*
            if (!method_exists($handler[0], $method)) {
                throw new BadMethodCallException("The method \"".$method."\" does not exist in the class (" . $handler[0]::class . ")", 1);
            }
            */
            if (is_null($handler[1][0]) || in_array($method, $handler[1])) {
                $this->bindable[$method] = $method;
            }
            $data = call_user_func_array([$handler[0], $method], $args);
        }

        if (isset($this->bindable[$method])) {
            $this->triggerEvents();
        }
        return $data;
    }

    /**
     * Trigger event
     * @return void
     */
    final protected function triggerEvents(): void
    {
        if (is_null($this->event)) {
            throw new \Exception("Event has not been initiated", 1);
        }


        foreach ($this->event as $key => $event) {
            if (is_array($event)) {
                $select = key($event);
                if (isset($this->bindable[$select])) {
                    $this->getEvent($event[$select]);
                }
            } else {
                $this->getEvent($event);
            }
            // Execute event once!
            if ($this->stopPropagate) {
                unset($this->event[$key]);
            }
        }
    }

    /**
     * Resolve event
     * @param  callable|object $data
     * @return void
     */
    final protected function getEvent(callable|object $data): void
    {   
        if(is_object($data)) {
            $data->resolve();
        } else {
            $data();
        }
    }
}
