<?php

namespace MaplePHP\Container\Exceptions;

use InvalidArgumentException;
use MaplePHP\Container\Interfaces\ContainerExceptionInterface;

/**
 * Base interface representing a generic exception in a container.
 */
class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface
{
}
