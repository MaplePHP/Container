
# Container, Factories and the dependency injector 
PSR Container built for MaplePHP framework

Container, Factories and dependency injectors will help to make your PHP code more maintainable, flexible, and testable by reducing coupling between objects and centralizing the management of dependencies.

## Container
Containers allowing you to easily create and retrieve objects that are needed throughout your application.
```php
use MaplePHP\Container\Container;
$container = new Container();
$container->set("YourClass", \YourNamespace\To\YourClass::class); // Bind "YourClass" to container and dependency injector
$yourClass = $container->get("YourClass")->get(); // Will return "YourClass"
//$yourClass->yourClassMehthod();
```
If the constructor of "YourClass" contains unresolved class arguments, the dependency injector will attempt to automatically locate them for you. Read more under the headline **dependency injector**.

## Factory
Factories can be used to create new instances of objects, rather than instantiating them directly in your code. 
```php
$container->factory("factoryKey", function() {
    $a = new TestClassA();
    $b = new TestClassB();
    return new TestClassC($a, $b);
});
echo $container->get("factoryKey"); // Will return TestClassC
```
## The dependency injector
Dependency injection is a technique for managing dependencies between objects in an application. Instead of creating objects directly in your code, you can pass them in as dependencies when you instantiate an object. This makes your code more modular and easier to test, as you can easily swap out dependencies for mock objects or other implementations.

You can use the **Dependency injector** just like create any other container, as long as you dont add arguments or try to access method, if you do that then it will automatically disable **Dependency injector**. It is design like this becouse it will load in all class reclusive into endlessly.

Take a look at this example

```php

$container->set("YourClass", \YourNamespace\To\YourClass::class);
$testService = $container->get("YourClass");
echo $testService->start();

```
The above code will load **YourClass** and auto initialize the class **Test**.

```php
namespace YourNamespace\To;

use YourNamespace\ToTestClasses\Test;

class YourClass {
    
    private $test;

    // Dependency injector will auto load "Test" class and the "Test" classes and so on.
    function __construct(Test $test) {
        $this->test = $test;
    }

    function start() {
        return $this->test->get("This is the start page");
    }
    
}
```
