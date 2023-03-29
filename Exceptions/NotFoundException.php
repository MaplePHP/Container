<?php

namespace PHPFuse\Container\Exceptions;

use PHPFuse\Container\Interfaces\NotFoundExceptionInterface;
use RuntimeException;

/**
 * No entry was found in the container.
 */
class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}