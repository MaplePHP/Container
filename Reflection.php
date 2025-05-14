<?php

declare(strict_types=1);

namespace MaplePHP\Container;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use MaplePHP\Container\Exceptions\NotFoundException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Reflection
{
    private ?string $method = null;
    private ReflectionClass $reflect;
    private ?array $args = null;
    private bool $allowInterfaces = true;
    private ?string $dependMethod = null;
    private static array $class = [];
    private static ?array $interfaceFactory = null;
    //private static array $attr = [];
    //private static $interfaceProtocol;

    /**
     * Start reflection of a class or method
     * @param class-string|object $classData
     * @throws ReflectionException
     */
    public function __construct(string|object $classData)
    {
        if (is_string($classData)) {
            if (($pos = strpos($classData, "::")) !== false) {
                $classData = substr($classData, 0, $pos);
                $this->method = substr($classData, $pos + 2);
            }
            if (!class_exists($classData)) {
                throw new NotFoundException("Could not find the class \"$classData\".", 1);
            }
        }
        $this->reflect = new ReflectionClass($classData);
    }

    /**
     * If the dependency injector tries to read an interface in controller it
     * will search for the object in interfaceFactory.
     * @param callable $call
     * @return void
     */
    public static function interfaceFactory(callable $call): void
    {
        self::$interfaceFactory[] = function ($short, $class, $reflect) use ($call) {
            //self::$interfaceProtocol[$short] = $call($class, $short, $reflect);
            return $call($short, $class, $reflect);
        };
    }

    /**
     * Allow interfaces
     * @param  bool   $bool
     * @return void
     */
    public function allowInterfaces(bool $bool): void
    {
        $this->allowInterfaces = $bool;
    }

    /**
     * Call dependency injector
     * @return object
     * @throws ReflectionException|Exception
     */
    public function dependencyInjector(?object $class = null, ?string $method = null): mixed
    {
        $args = [];
        $constructor = $this->setDependMethod($method, $this->reflect);
        if ($constructor !== null) {
            $params = $constructor->getParameters();
            $this->injectRecursion($params, $this->reflect->getName());
            foreach ($params as $param) {
                if (!$this->isBuiltin($param)) {
                    $classKey = $this->getClassNameFromParam($param);
                    if (!$classKey) {
                        continue;
                    }

                    if (isset(self::$class[$classKey])) {
                        $args[] = self::$class[$classKey];
                    }
                }
            }
        }
        if ($this->dependMethod !== null) {
            $this->dependMethod = null;
            return $constructor->invokeArgs($class, $args);
        }
        return $this->reflect->newInstanceArgs($args);
    }

    /**
     * Set dependent method
     * @param string|null $method
     * @param ReflectionClass $inst
     * @return ReflectionMethod|null
     * @throws ReflectionException
     */
    public function setDependMethod(?string $method, ReflectionClass $inst): ?ReflectionMethod
    {
        $method = ($method === "constructor") ? null : $method;
        $this->dependMethod = $method;
        if ($this->dependMethod === null) {
            return $inst->getConstructor();
        }
        return $inst->getMethod($this->dependMethod);
    }

    /**
     * Check if a parameter type is built-in
     *
     * @param ReflectionParameter $param Parameter to check
     * @return bool Returns true if a parameter type is not built-in, false otherwise
     */
    private function isBuiltin(ReflectionParameter $param): bool
    {
        $type = $param->getType();
        return ($type instanceof ReflectionNamedType && $type->isBuiltin());
    }

    /**
     * This will return reflection if class exist or error pointing to file where error existed,
     * @param class-string $className
     * @param class-string $fromClass
     * @return ReflectionClass
     * @throws Exception
     */
    private function initReclusiveReflect(string $className, string $fromClass): ReflectionClass
    {
        try {
            return new ReflectionClass($className);
        } catch (Exception $e) {
            if (!class_exists($className)) {
                throw new NotFoundException('Class "' . $className . '" does not exist in the class "' . $fromClass . '".', 1);
            } else {
                throw new Exception($e->getMessage() . '. You might want to check the file ' . $fromClass . '.', 1);
            }
        }
    }

    /**
     * Recursion inject dependencies
     * @param array $params
     * @param class-string $fromClass
     * @param array $_args
     * @return array
     * @throws Exception
     */
    private function injectRecursion(array $params, string $fromClass, array $_args = []): array
    {
        $_args = [];
        foreach ($params as $param) {
            if (!$this->isBuiltin($param)) {
                $classNameA = $this->getClassNameFromParam($param);
                if (!$classNameA) {
                    continue; // skip if class name couldn't be resolved
                }
                $inst = $this->initReclusiveReflect($classNameA, $fromClass);
                $reflectParam = [];
                $constructor = $inst->getConstructor();

                if (!$inst->isInterface()) {
                    $reflectParam = ($constructor) ? $constructor->getParameters() : [];
                }

                if (count($reflectParam) > 0) {
                    $_args = $this->injectRecursion($reflectParam, $inst->getName(), $_args);

                    // Will make it possible to set same instance in multiple nested classes
                    $_args = $this->insertMultipleNestedClasses($inst, $constructor, $classNameA, $reflectParam);
                } else {
                    if ($inst->isInterface()) {
                        $this->insertInterfaceClasses($inst, $classNameA);
                    } else {
                        if (empty(self::$class[$classNameA])) {
                            self::$class[$classNameA] = $this->newInstance($inst, (bool)$constructor, $_args);
                        }
                    }
                    $_args[] = self::$class[$classNameA];
                }
            }
        }
        return $_args;
    }

    /**
     * Extracts the class name from a ReflectionParameter object
     * Handles named types, union types, and intersection types (PHP 8.1+)
     * Returns the first non-built-in type name found or null if none exists
     *
     * @param ReflectionParameter $param The reflection parameter to analyze
     * @return string|null The extracted class name or null if no class name is found
     */
    private function getClassNameFromParam(ReflectionParameter $param): ?string
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ReflectionNamedType && !$innerType->isBuiltin()) {
                    return $innerType->getName();
                }
            }
        }

        // Optionally handle ReflectionIntersectionType for PHP 8.1+
        if (PHP_VERSION_ID >= 80100 && $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ReflectionNamedType && !$innerType->isBuiltin()) {
                    return $innerType->getName(); // or return all names if needed
                }
            }
        }
        return null;
    }


    /**
     * Will insert interface classes (the default classes)
     * @param  ReflectionClass $inst
     * @param  string          $classNameA
     * @return void
     */
    private function insertInterfaceClasses(ReflectionClass $inst, string $classNameA): void
    {
        if ($this->allowInterfaces) {
            if (self::$interfaceFactory !== null) {
                foreach (self::$interfaceFactory as $call) {
                    self::$class[$classNameA] = $call($inst->getShortName(), $classNameA, $inst);
                }
            }
        } else {
            self::$class[$classNameA] = null;
        }
    }

    /**
     * Will make it possible to set same instance in multiple nested classes
     * @param ReflectionClass $inst
     * @param ReflectionMethod|null $constructor
     * @param string $classNameA
     * @param array $reflectParam
     * @return array
     * @throws ReflectionException
     */
    private function insertMultipleNestedClasses(
        ReflectionClass $inst,
        ?ReflectionMethod $constructor,
        string $classNameA,
        array $reflectParam
    ): array {
        $args = [];
        foreach ($reflectParam as $reflectInstance) {
            if (!$this->isBuiltin($reflectInstance)) {
                $classNameB = $reflectInstance->getType()->getName();
                if (isset(self::$class[$classNameB])) {
                    $args[] = self::$class[$classNameB];
                }
            }
        }
        if (empty(self::$class[$classNameA])) {
            self::$class[$classNameA] = $this->newInstance($inst, (bool)$constructor, $args);
        }
        return $args;
    }

    /**
     * Create an instance from reflection
     * @param ReflectionClass $inst
     * @param bool $hasCon
     * @param array $args
     * @return  object
     * @throws ReflectionException
     */
    public function newInstance(ReflectionClass $inst, bool $hasCon, array $args): object
    {
        if ($hasCon) {
            return $inst->newInstanceArgs($args);
        }
        return $inst->newInstance();
    }

    /**
     * Set arguments to constructor or method (depending on how $data in new Reflection($data) is defined).
     * IF method is set then method arguments will be passed, (the method will be treated as a static method)
     * @param array $array [description]
     */
    public function setArgs(array $array): self
    {
        $this->args = $array;
        return $this;
    }

    /**
     * Access reflection class
     * @return ReflectionClass
     */
    public function getReflect(): ReflectionClass
    {
        return $this->reflect;
    }

    /**
     * Get the loaded container data
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    public function get(): mixed
    {
        if ($this->method !== null) {
            $method = $this->reflect->getMethod($this->method);
            if ($method->isConstructor()) {
                return $this->getClass();
            }
            if ($method->isDestructor()) {
                throw new Exception("You can not set a Destructor as a container", 1);
            }
            $inst = $this->reflect->newInstanceWithoutConstructor();

            if ($this->args !== null) {
                return $method->invokeArgs($inst, $this->args);
            } else {
                return $method->invoke($inst);
            }
        }
        return $this->getClass();
    }

    public static function getClassList(): array
    {
        return self::$class;
    }

    /**
     * Load dependencyInjector / or just a container
     * @return object
     * @throws ReflectionException
     */
    private function getClass(): object
    {
        if ($this->args !== null) {
            $inst = $this->reflect->newInstanceArgs($this->args);
        } else {
            $inst = $this->dependencyInjector();
        }
        return $inst;
    }


    /*
    // Possible attribute snippet in working progress
    function propagateAttr($reflectionClass, $class) {
        foreach ($reflectionClass->getMethods() as $method) {
            $attributes = $method->getAttributes(ListensTo::class);

            foreach ($attributes as $attribute) {
                $listener = $attribute->newInstance();
                $name = $method->getName();
                self::$attr[$name] = [
                    $listener,
                    $name
                ];
            }
        }
    }
     */
}
