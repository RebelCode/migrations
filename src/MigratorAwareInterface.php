<?php

namespace RebelCode\Migrations;

/**
 * Something that is aware of, and can provide, a migrator instance.
 *
 * @since [*next-version*]
 */
interface MigratorAwareInterface
{
    /**
     * Retrieves the migrator associated with this instance.
     *
     * @since [*next-version*]
     *
     * @return MigratorInterface|null The migrator, or null.
     */
    public function getMigrator();
}
