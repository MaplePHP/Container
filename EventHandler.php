<?php

declare(strict_types=1);

namespace MaplePHP\Container;

use Exception;
use MaplePHP\Container\Interfaces\EventInterface;
use ReflectionException;

/**
 * Create an event handler that will listen to a certain method in class.
 * Note: This will change to a valid PSR-14 event dispatcher library in the future.
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
     * @param string|array|null $method
     * @return void
     * @throws ReflectionException
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
     * @param callable|object|string $event
     * @param string|null $bind
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function addEvent(callable|object|string $event, ?string $bind = null): void
    {

        if (!is_callable($event)) {
            if (is_string($event)) {
                $reflect = new Reflection($event);
                $event = $reflect->get();
            }

            if (is_object($event) && !($event instanceof EventInterface)) {
                throw new Exception("Event object/class needs to be instance of \"EventInterface\"!", 1);
            }
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
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws Exception
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
        if (is_array($this->event)) {
            foreach ($this->event as $key => $event) {
                if (is_array($event)) {
                    $select = key($event);
                    $this->getEvent($event[$select]);
                } else {
                    $this->getEvent($event);
                }
                // Stop propagate make sure event is executed once
                if ($this->stopPropagate) {
                    unset($this->event[$key]);
                }
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
        if (is_callable($data)) {
            $data();
        } else {
            $data->resolve();
        }
    }
}
