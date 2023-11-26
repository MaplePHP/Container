<?php

declare(strict_types=1);

namespace MaplePHP\Container\Interfaces;

/**
 * The Factory Container Interface.
 */
interface FactoryInterface
{
    /**
     * Same as @set, BUT will only accept a factory
     * @param  string       $identifier Uniq identifier
     * @param  callable     $factory
     * @param  bool|boolean $overwrite Will throw exception if already been defined if not arg is set to TRUE.
     * @return self
     */
    public function factory(string $identifier, callable $factory, bool $overwrite = false);
}
