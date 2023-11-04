<?php

declare(strict_types=1);

namespace PHPFuse\Container\Interfaces;

/**
 * The Factory Container Interface.
 */
interface FactoryInterface
{
    /**
     * Add a single factory.
     *
     * @param string $id The id
     * @param callable $factory The callable
     * @param bool $factory The callable
     * @param bool|boolean $overwrite Will throw exception if already been defined if not arg is set to TRUE.
     * @return void
     */
    public function factory(string $id, callable $factory, bool $overwrite = false): ContainerInterface;
}
