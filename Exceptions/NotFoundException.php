<?php

namespace MaplePHP\Container\Exceptions;

use MaplePHP\Container\Interfaces\NotFoundExceptionInterface;
use RuntimeException;

/**
 * No entry was found in the container.
 */
class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
