<?php

namespace PHPFuse\Container\Exceptions;

use InvalidArgumentException;
use PHPFuse\Container\Interfaces\ContainerExceptionInterface;

/**
 * Base interface representing a generic exception in a container.
 */
class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface
{
}
