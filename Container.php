<?php 

declare(strict_types=1);

namespace PHPFuse\Container;

use PHPFuse\Container\Interfaces\ContainerInterface;
use PHPFuse\Container\Interfaces\FactoryInterface;
use PHPFuse\DTO\Format\Arr;
use PHPFuse\Container\Reflection;
use PHPFuse\Container\Exceptions\NotFoundException;
use PHPFuse\Container\Exceptions\ContainerException;

class Container implements ContainerInterface, FactoryInterface
{
    private $services = array();
    private $args;
    private $overwrite;
    private $getter = array();


    function __call($a, $b) {
        return $this->get($a, $b);
    }

    /**
     * Set a container OR factory
     * @param string       $id        Uniq identifier
     * @param mixed        $value     Example: TestClasses\Test::class, TestClasses\Test::class."::__construct", TestClasses\Test::class."::getStaticMethod",
     * @param array|null   $args      Pass argumnets to constructor staticMethod if you choose.
     * @param bool|boolean $overwrite Will throw exception if already been defined if not arg is set to TRUE.
     */
    public function set(string $id, $value, ?array $args = NULL, bool $overwrite = false): ContainerInterface 
    {
        if(!$overwrite && $this->has($id)) {
            $type = ($this->isFactory($id)) ? "factory" : "container";
            throw new ContainerException("The {$type} ({$id}) has already been defined. If you want to overwrite the {$type} then set overwrite argument to true.", 1);
        }

        if(isset($this->getter[$id])) unset($this->getter[$id]);
        $this->services[$id] = $value;
        $this->args[$id] = $args;
        return $this;
    }

    /**
     * Same as @set, BUT will only accept a factory
     * @param  string       $id Uniq identifier
     * @param  callable     $factory
     * @param  bool|boolean $overwrite Will throw exception if already been defined if not arg is set to TRUE.
     * @return void
     */
    public function factory(string $id, callable $factory, bool $overwrite = false): ContainerInterface 
    {
        if(!$overwrite && $this->has($id)) {
            if(!$this->isFactory($id)) {
                throw new ContainerException("({$id}) Has already been defined, but has been defined as a container and not factory. If you want to overwrite the container as factory then set overwrite argument to true.", 1);
            } else {
                throw new ContainerException("The factory ({$id}) has already been defined. If you want to overwrite the factory then set overwrite argument to true.", 1);
            }
        }

        if(isset($this->getter[$id])) unset($this->getter[$id]);
        $this->services[$id] = $factory;
        return $this;
    }

    /**
     * Check if service exist
     * @param  string  $id
     * @return boolean
     */
    public function has(string $id): bool 
    {
        return (bool)($this->getService($id));
    }

    /**
     * Check if is a factory
     * @param  string  $id Uniq identifier
     * @return boolean
     */
    function isFactory(string $id) 
    {
        return (bool)($this->getService($id) instanceof \Closure);
    }

    /**
     * Check if is a container
     * @param  string  $id Uniq identifier
     * @return boolean
     */
    function isContainer($id) 
    {
        return (bool)(!$this->isFactory($id));
    }

    /**
     * Get a container or factory
     * @param  string     $id   [description]
     * @param  array $args Is possible to overwrite/add __construct or method argumnets
     * @return mixed
     */
    public function get(string $id, array $args = []) 
    {
        if($service = $this->getService($id)) {
            if(is_null($args)) $args = $this->getArgs($id);

            if(($service instanceof \Closure)) {
                $this->getter[$id] = $service(...$args);
                
            } else {
                if(empty($this->getter[$id])) {
                    if(is_string($service)) {
                        $reflect = new Reflection($service);
                        if(!is_null($args)) $reflect->setArgs($args);
                        $this->getter[$id] = $reflect->get();
                    } else {
                        $this->getter[$id] = $service;
                    }
                }
            }
    
            
            return $this->getter[$id];

        } else {
            throw new NotFoundException("Tring to get a container ({$id}) that does not exists", 1);
        }
    }
    

    /**
     * Fetch is used to load multiple container and factories at once with the help of a wildcard search
     * @example     @set("event.session", \name\space\session::class)
     *              ->set("event.traverse", \name\space\traverse::class)
     *              ->fetch("event.*");
     * @param       string $id
     * @return      mixed
     */
    public function fetch(string $id)
    {
        if(strpos($id, "*") !== false) {
            $arr = Arr::value($this->services)->wildcardSearch($id)->get();
            if(count($arr) > 0) {
                $new = array();
                foreach($arr as $key => $value) {
                    $new[$key] = $this->get($key);         
                }
                return $new;
            }
        }
        throw new NotFoundException("Error Processing Request", 1);
    }

    /**
     * Get services
     * @param  string $id
     * @return array|null
     */
    private function getService(string $id) 
    {
        return ($this->services[$id] ?? NULL);
    }

    /**
     * Get arguments
     * @param  string $id
     * @return array|null
     */
    private function getArgs(string $id) 
    {
        return ($this->args[$id] ?? NULL);
    }



}
