<?php 

declare(strict_types=1);

namespace PHPFuse\Container;

use ReflectionClass;
use PHPFuse\Container\Exceptions\NotFoundException;

class Reflection
{
    private $method;
    private $reflect;
    private $args;
    private $parameters;
    private $allowInterfaces = true;


    private static $class = array();
    private static $interfaceFactory;
    private static $interfaceProtocol;

    /**
     * Start relection of a class or method
     * @param string|object $data
     */
    function __construct($data)
    {
        $class = $data;
        if(is_string($data) && ($pos = strpos($data, "::")) !== false) {
            $class = substr($data, 0, $pos);
            $this->method = substr($data, $pos+2);
        }
        $this->reflect = new ReflectionClass($class);
    }

    /**
     * If the dependency injector tries to read an interface in controller it will search for the object in interfaceFactory.
     * @return controller
     */
    public static function interfaceFactory($call): void
    {
        self::$interfaceFactory[] = function($class, $short, $reflect) use($call) {
            // self::$interfaceProtocol[$short] = $call($class, $short, $reflect);
            return $call($class, $short, $reflect);
        };
    }

    /**
     * Allow interfaces
     * @param  bool   $bool
     * @return void
     */
    function allowInterfaces(bool $bool): void 
    {
        $this->allowInterfaces = $bool;

    }

    /**
     * Call dependency injector
     * @return controller
     */
    function dependencyInjector()
    {
        $params = $this->reflect->getConstructor()->getParameters();
        $this->injectRecursion($params, $this->reflect->getName());
        
        $args = array();
        foreach($params as $param) {
            if($param->getType() && !$param->getType()->isBuiltin()) {
                $a = $param->getType()->getName();
                if(isset(self::$class[$a])) $args[] = self::$class[$a];
            }
        }
        return $this->reflect->newInstanceArgs($args);
    }

    /**
     * This will return reflection if class exist or error pointing to file where error existed,
     * @param  string $className
     * @param  string $fromClass
     * @return ReflectionClass
     */
    private function initReclusiveReflect(string $className, string $fromClass): ReflectionClass {
        try {
           return new ReflectionClass($className);
        } catch (\Exception $e) {
            if(!class_exists($className)) {
                throw new NotFoundException('Class "'.$className.'" does not exist in the class "'.$fromClass.'".', 1);
            } else {
                throw new \Exception($e->getMessage().'. You might want to check the file '.$fromClass.'.', 1);   
            }
        }
    }

    /**
     * Recursion inject dependancies 
     * @param  array  $params
     * @param  array  $args
     * @return array
     */
    private function injectRecursion(array $params, string $fromClass, array $args = array()) 
    {
        $args = array();
        foreach($params AS $k => $param) {
            if($param->getType() && !$param->getType()->isBuiltin()) {
                $a = $param->getType()->getName();

                
                $inst = $this->initReclusiveReflect($a, $fromClass);

                $p = array();
                $con = $inst->getConstructor();
                if(!$inst->isInterface()) $p = ($con) ? $con->getParameters() : [];

                if(count($p) > 0) {
                    $args = $this->injectRecursion($p, $inst->getName(), $args);

                    // Will make it posible to set same instance in multiple nested classes
                    $args = array();
                    foreach($p as $p2) {
                        if($p2->getType() && !$p2->getType()->isBuiltin()) {
                            $a2 = $p2->getType()->getName();
                            if(isset(self::$class[$a2])) $args[] = self::$class[$a2];
                        }
                    }
                    if(empty(self::$class[$a])) self::$class[$a] = $this->newInstance($inst, (bool)$con, $args);

                } else {
                    if($inst->isInterface())  {

                        if($this->allowInterfaces) {
                            if(!is_null(self::$interfaceFactory)) {
                                foreach(self::$interfaceFactory as $call) self::$class[$a] = $call($a, $inst->getShortName(), $inst);
                            }
                            
                        } else {
                            self::$class[$a] = NULL;
                        }

                    } else {
                        if(empty(self::$class[$a])) self::$class[$a] = $this->newInstance($inst, (bool)$con, $args);
                    }
                    $args[] = self::$class[$a];
                }
            }
        }
        return $args;
    }

    /**
     * Create a instance from reflection
     * @param  ReflectionClass $inst
     * @param  bool   $hasCon
     * @param  array  $args
     * @return  object
     */
    function newInstance(ReflectionClass $inst, bool $hasCon, array $args) {
        if($hasCon) {
            return $inst->newInstanceArgs($args);
        }
        return $inst->newInstance();
    }
   
    /**
     * Set argumnets to constructor or method (depending on how $data in new Reflection($data) is defined).
     * IF method is set then method arguments will be passed, (the method will be treated as a static method)
     * @param array $array [description]
     */
    function setArgs(array $array): self 
    {
        $this->args = $array;
        return $this;
    }

    /**
     * Access reflection class
     * @return ReflectionClass
     */
    function getReflect(): ReflectionClass 
    {
        return $this->reflect;
    }

    /**
     * Get the loaded container data
     * @return mixed
     */
    function get() 
    {
        if(!is_null($this->method)) {
            $method = $this->reflect->getMethod($this->method);
            if($method->isConstructor()) return $this->getClass();
            if($method->isDestructor()) throw new \Exception("You can not set a Destructor as a container", 1);
            $inst = $this->reflect->newInstanceWithoutConstructor();

            if(!is_null($this->args)) {
                return $method->invokeArgs($inst, $this->args);
            } else {
                return $method->invoke($inst);
            }
        }
        return $this->getClass();
    }

    static function getClassList() {
        return self::$class;
    }

    /**
     * Load dependencyInjector / or just a container
     * @return instance
     */
    private function getClass() 
    {
        if(!is_null($this->args)) {
            $inst = $this->reflect->newInstanceArgs($this->args);
        } else {
            $inst = $this->dependencyInjector();
        }
        return $inst;
    }

}
