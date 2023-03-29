<?php

namespace PHPFuse\Container\tests\TestClasses;


class Test {

    protected $title = "Not set";

    function __construct($title = "None is set") {
        $this->title = $title;
    }

    public static function _get($arg = "Daiel") {
        return "Lorem {$arg} ipsum";
    }

    function get(?string $title = NULL) {

        if(!is_null($title)) $this->title = $title;
        return $this->title;
    }
}
