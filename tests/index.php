<?php

// Place Codes/snippets at top of test file

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPFuse;
use PHPFuse\Container\Container;
use PHPFuse\Container\tests\TestClasses\TestClass;

$dir = dirname(__FILE__)."/../";

require_once("{$dir}../_vendors/composer/vendor/autoload.php");

spl_autoload_register(function ($class) use ($dir) {
    $classFilePath = null;
    $class = str_replace("\\", "/", $class);
    $exp = explode("/", $class);
    $sh1 = array_shift($exp);
    $sh2 = array_shift($exp);
    $path = implode("/", $exp).".php";
    $filePath = $dir.$path;

    if (!is_file($filePath)) {
        //$filePath = "{$dir}../{$class}.php";
        $filePath = $dir."../".$sh2."/".$path;
    }
    require_once($filePath);
});

// Laddas i Emitter
$container = new Container();



$container->set("testDipendencyInjector", '\PHPFuse\Container\tests\Controllers\TestController');
$test = $container->get("testDipendencyInjector");
echo $test->start()."<br><br>";


$container->set("test1", TestClass::class); // Will load TestClass and set argumnet to constructor
$container->set("test2", TestClass::class, ["Test"]); // Will load TestClass and set argumnet to constructor
$container->set("test3", TestClass::class."::testGet", ["Test 2"]);

echo $container->get("test1")->get()."<br>";
echo $container->get("test2", ["Test 2 (overwritten)"])->get()."<br>";
echo $container->get("test3")."<br>";




echo "<br>";


$container->set("event.ev1", function () {
    $test = new TestClass("ev1");
    echo $test->get()."<br>";
});

$container->set("event.ev2", function () {
    $test = new TestClass("ev2");
    echo $test->get()."<br>";
});

$container->set("event.ev3", function () {
    $test = new TestClass("ev3");
    echo $test->get()."<br>";
});


$container->fetch("event.*");
