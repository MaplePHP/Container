
# Container, Factories and the dependency injector 
PSR Container built for PHP Fuse framework

Container, Factories and dependency injectors will help to make your PHP code more maintainable, flexible, and testable by reducing coupling between objects and centralizing the management of dependencies.

## Container
Containers allowing you to easily create and retrieve objects that are needed throughout your application.
```php
use PHPFuse\Container\tests\TestClasses\TestClass;

$container->set("test1", TestClass::class); // Will load TestClass
$container->set("test2", TestClass::class, ["Test"]); // Will load TestClass and set argumnet to constructor
$container->set("test3", TestClass::class."::_get", ["Test 2"]); // Will load TestClass and static method named "_get" and set argumnet to that method

echo $container->get("test1")->get(); // Will echo @TestClass->get()
echo $container->get("test2", ["Test 2 (overwritten)"])->get();  // Will echo @TestClass->get("Test 2 (overwritten)")
echo $container->get("test3"); // Will echo @TestClass::_get()
```
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
use PHPFuse\Container\tests\TestClasses\TestClass;
$container->set("uniqueKey", TestClass:class);
// $container->set("uniqueKey", '\PHPFuse\Container\tests\Controllers\TestController'); // Same as above
$testController = $container->get("uniqueKey");
echo $testController->start();

```
The above code will load **TestController** and auto initialize the class **Test**.

```php
namespace PHPFuse\Container\tests\Controllers;

use PHPFuse\Container\tests\TestClasses\Test;

class TestController {
    
    private $test;

    function __construct(Test $test) {
        $this->test = $test;
    }

    function start() {
        return $this->test->get("This is the start page");
    }
    
}
```
