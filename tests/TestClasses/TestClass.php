<?php

namespace PHPFuse\Container\tests\TestClasses;

class TestClass
{
    protected $title = "Not set";

    public function __construct($title = "None is set")
    {
        $this->title = $title;
    }

    public static function testGet($arg = "Daiel")
    {
        return "Lorem {$arg} ipsum";
    }

    public function get(?string $title = null)
    {

        if (!is_null($title)) {
            $this->title = $title;
        }
        return $this->title;
    }
}
