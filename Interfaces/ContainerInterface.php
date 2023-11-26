<?php

declare(strict_types=1);

namespace MaplePHP\Container\Interfaces;

/**
 * Describes the interface of a container that exposes methods to read its entries.
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $identifier Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $identifier, array $args = []): mixed;

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($identifier)` returning true does not mean that `get($identifier)` will not throw an exception.
     * It does however mean that `get($identifier)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $identifier Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $identifier): bool;


    /**
     * Set a container OR factory
     * @param string       $identifier  Uniq identifier
     * @param mixed        $value       Example:
     *                                  TestClasses\Test::class,
     *                                  TestClasses\Test::class."::__construct",
     *                                  TestClasses\Test::class."::getStaticMethod",
     * @param array|null   $args        Pass argumnets to constructor staticMethod if you choose.
     * @param bool|boolean $overwrite   Will throw exception if already been defined if not arg is set to TRUE.
     */
    public function set(string $identifier, $value, ?array $args = null, bool $overwrite = false): ContainerInterface;
}
