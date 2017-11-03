<?php

namespace RebelCode\Migrations;

use Dhii\Util\String\StringableInterface as Stringable;

/**
 * Something that can migrate a database.
 *
 * @since [*next-version*]
 */
interface MigratorInterface
{
    /**
     * Update the database, either up to a specific version or to the latest version.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable|null $version The version to migrate up to.
     */
    public function up($version = null);

    /**
     * Rollback the database, either down to a specific version or as much as possible.
     *
     * @param int|string|Stringable|null $version The version to rollback down to.
     */
    public function down($version = null);

    /**
     * Resets the database to the base version.
     *
     * @since [*next-version*]
     */
    public function reset();
}
