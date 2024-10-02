<?php

declare(strict_types=1);

namespace MaplePHP\Container;

use Closure;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Container\Interfaces\FactoryInterface;
use MaplePHP\DTO\Format\Arr;
//use MaplePHP\Container\Reflection;
use MaplePHP\Container\Exceptions\NotFoundException;
use MaplePHP\Container\Exceptions\ContainerException;
use ReflectionException;

class Container implements ContainerInterface, FactoryInterface
{
    private array $services = [];
    private array $args = [];
    private array $getter = [];

    /**
     * @throws ReflectionException
     */
    public function __call($method, $args)
    {
        return $this->get($method, $args);
    }

    /**
     * Set a container OR factory
     * @param string       $identifier  Uniq identifier
     * @param mixed        $value       Example:
     *                                  TestClasses\Test::class,
     *                                  TestClasses\Test::class."::__construct",
     *                                  TestClasses\Test::class."::getStaticMethod",
     * @param array|null   $args        Pass argumnets to constructor staticMethod if you choose.
     * @param bool $overwrite   Will throw exception if already been defined if not arg is set to TRUE.
     */
    public function set(string $identifier, $value, ?array $args = null, bool $overwrite = false): ContainerInterface
    {
        if (!$overwrite && $this->has($identifier)) {
            $type = ($this->isFactory($identifier)) ? "factory" : "container";
            throw new ContainerException("The $type ($identifier) has already been defined. If you want to overwrite " .
                "the $type then set overwrite argument to true.", 1);
        }

        if (isset($this->getter[$identifier])) {
            unset($this->getter[$identifier]);
        }
        $this->services[$identifier] = $value;
        $this->args[$identifier] = $args;
        return $this;
    }

    /**
     * Same as @set, BUT will only accept a factory
     * @param  string       $identifier Uniq identifier
     * @param  callable     $factory
     * @param  bool $overwrite Will throw exception if already been defined if not arg is set to TRUE.
     * @return self
     */
    public function factory(string $identifier, callable $factory, bool $overwrite = false): self
    {
        if (!$overwrite && $this->has($identifier)) {
            if (!$this->isFactory($identifier)) {
                throw new ContainerException("($identifier) Has already been defined, but has been defined as a " .
                    "container and not factory. If you want to overwrite the container as factory then set " .
                    "overwrite argument to true.", 1);
            } else {
                throw new ContainerException("The factory ($identifier) has already been defined. If you want to " .
                    "overwrite the factory then set overwrite argument to true.", 1);
            }
        }

        if (isset($this->getter[$identifier])) {
            unset($this->getter[$identifier]);
        }
        $this->services[$identifier] = $factory;
        return $this;
    }

    /**
     * Check if service exist
     * @param  string  $identifier
     * @return boolean
     */
    public function has(string $identifier): bool
    {
        return (bool)($this->getService($identifier));
    }

    /**
     * Check if is a factory
     * @param  string  $identifier Uniq identifier
     * @return boolean
     */
    public function isFactory(string $identifier): bool
    {
        return ($this->getService($identifier) instanceof Closure);
    }

    /**
     * Check if is a container
     * @param string $identifier Uniq identifier
     * @return boolean
     */
    public function isContainer(string $identifier): bool
    {
        return (!$this->isFactory($identifier));
    }

    /**
     * Get a container or factory
     * @param string $identifier [description]
     * @param array $args Is possible to overwrite/add __construct or method argumnets
     * @return mixed
     * @throws ReflectionException
     */
    public function get(string $identifier, array $args = []): mixed
    {
        if ($service = $this->getService($identifier)) {
            if (count($args) === 0) {
                $args = $this->getArgs($identifier);
            }
            if ($this->isFactory($identifier)) {
                $this->getter[$identifier] = $service(...$args);
            } else {
                if (empty($this->getter[$identifier])) {
                    if (is_string($service) && class_exists($service)) {
                        $reflect = new Reflection($service);
                        if (count($args) > 0) {
                            $reflect->setArgs($args);
                        }
                        $this->getter[$identifier] = $reflect->get();
                    } else {
                        $this->getter[$identifier] = $service;
                    }
                }
            }
            return $this->getter[$identifier];
        } else {
            throw new NotFoundException("Trying to get a container ($identifier) that does not exists", 1);
        }
    }


    /**
     * Fetch is used to load multiple container and factories at once with the help of a wildcard search
     * @param string $identifier
     * @return      array
     * @throws ReflectionException
     * @example     @set("event.session", \name\space\session::class)
     *              ->set("event.traverse", \name\space\traverse::class)
     *              ->fetch("event.*");
     */
    public function fetch(string $identifier): array
    {
        if (str_contains($identifier, "*")) {
            $arr = Arr::value($this->services)->wildcardSearch($identifier)->get();
            if (count($arr) > 0) {
                $new = [];
                foreach ($arr as $key => $_unusedValues) {
                    $new[$key] = $this->get($key);
                }
                return $new;
            }
        }
        throw new NotFoundException("Error Processing Request", 1);
    }

    /**
     * Get services
     * @param  string $identifier
     * @return mixed
     */
    private function getService(string $identifier): mixed
    {
        return ($this->services[$identifier] ?? null);
    }

    /**
     * Get arguments
     * @param  string $identifier
     * @return array
     */
    private function getArgs(string $identifier): array
    {
        return ($this->args[$identifier] ?? []);
    }
}
