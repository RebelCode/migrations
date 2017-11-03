<?php

namespace RebelCode\Migrations\Exception;

use Dhii\Util\String\StringableInterface as Stringable;

/**
 * An exception thrown when a migration fails.
 *
 * @since [*next-version*]
 */
interface CouldNotMigrateExceptionInterface extends
    MigratorExceptionInterface
{
    /**
     * Retrieves the migration version that was attempted.
     *
     * @since [*next-version*]
     *
     * @return int|string|Stringable|null The migration version, if any.
     */
    public function getMigrationVersion();
}
