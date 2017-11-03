<?php

namespace RebelCode\Migrations\Exception;

use Dhii\Util\String\StringableInterface as Stringable;
use Exception as RootException;
use RebelCode\Migrations\MigratorInterface;

/**
 * Concrete implementation of an exception thrown when a migration fails.
 *
 * @since [*next-version*]
 */
class CouldNotMigrateException extends AbstractMigratorException implements CouldNotMigrateExceptionInterface
{
    /**
     * The migration version that failed.
     *
     * @since [*next-version*]
     *
     * @var int|string|Stringable|null
     */
    protected $migrationVersion;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null     $message          The error message, if any.
     * @param int|null                   $code             The error code, if any.
     * @param RootException|null         $previous         The previous exception for chaining, if any.
     * @param MigratorInterface|null     $migrator         The migrator instance, if any.
     * @param int|string|Stringable|null $migrationVersion The migration version that failed, if any.
     */
    public function __construct(
        $message = null,
        $code = null,
        RootException $previous = null,
        $migrator = null,
        $migrationVersion = null
    ) {
        parent::__construct((string) $message, (int) $code, $previous);

        $this->_setMigrator($migrator);
        $this->_setMigrationVersion($migrationVersion);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getMigrator()
    {
        return $this->_getMigrator();
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getMigrationVersion()
    {
        return $this->migrationVersion;
    }

    /**
     * Sets the migration version that failed.
     *
     * @since [*next-version*]
     *
     * @param int|string|Stringable|null $migrationVersion The migration version or null.
     */
    protected function _setMigrationVersion($migrationVersion)
    {
        $this->migrationVersion = $migrationVersion;
    }
}
