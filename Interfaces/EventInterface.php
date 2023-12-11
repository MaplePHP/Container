<?php

declare(strict_types=1);

namespace MaplePHP\Container\Interfaces;

/**
 * Used to pass a service to the event handler
 */
interface EventInterface
{

    public function resolve(): void;
}
