<?php

namespace PHPFuse\Container\tests\Controllers;

use PHPFuse\Container\tests\TestClasses\Test;

class TestController
{
    private $test;

    public function __construct(Test $test)
    {
        $this->test = $test;
    }

    public function start()
    {
        return $this->test->get("This is the start page");
    }
}
