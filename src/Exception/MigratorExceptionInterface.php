<?php

namespace RebelCode\Migrations\Exception;

use Dhii\Exception\ThrowableInterface;
use RebelCode\Migrations\MigratorAwareInterface;

/**
 * An exception thrown in relation to a migrator.
 *
 * @since [*next-version*]
 */
interface MigratorExceptionInterface extends
    ThrowableInterface,
    MigratorAwareInterface
{
}
