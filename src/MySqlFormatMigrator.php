<?php

namespace RebelCode\Migrations;

use ByJG\DbMigration\Database\DatabaseInterface;
use ByJG\Util\Uri;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Util\Normalization\NormalizeIntCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception as RootException;
use Psr\Http\Message\UriInterface;
use RebelCode\Migrations\Exception\CouldNotMigrateException;
use RebelCode\Migrations\Exception\CouldNotMigrateExceptionInterface;
use RebelCode\Migrations\Exception\MigratorException;
use RebelCode\Migrations\Exception\MigratorExceptionInterface;

/**
 * An SQL migrator that allows formatting of SQL queries via placeholder replacement.
 *
 * @since [*next-version*]
 */
class MySqlFormatMigrator extends AbstractMigrator implements MigratorInterface
{
    /*
     * Provides integer normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeIntCapableTrait;

    /*
     * Provides string normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeStringCapableTrait;

    /*
     * Provides capability for creating invalid argument exceptions.
     *
     * @since [*next-version*]
     */
    use CreateInvalidArgumentExceptionCapableTrait;

    /*
     * Provides string translating functionality.
     *
     * @since [*next-version*]
     */
    use StringTranslatingTrait;

    /**
     * The name of the directory inside the base directory where migration files are stored,
     * Or null if no migrations directory is used in the root directory.
     *
     * @since [*next-version*]
     */
    const MIGRATIONS_DIRECTORY = 'migrations';

    /**
     * The formatters, as an associative array of callbacks.
     *
     * @since [*next-version*]
     *
     * @var callable[]
     */
    protected $formatters;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param UriInterface      $uri        The database URI.
     * @param DatabaseInterface $db         The database adapter.
     * @param string            $baseDir    The base directory, where the migrations directory and base SQLk are found.
     * @param array             $formatters The formatters, as an associative array of callbacks.
     */
    public function __construct(UriInterface $uri, DatabaseInterface $db, $baseDir, $formatters = [])
    {
        $this->uri     = $uri;
        $this->_folder = $baseDir;

        $this->_dbCommand = $db;
        $this->formatters = $formatters;
    }

    /**
     * Update the database, either up to a specific version or to the latest version.
     *
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function up($version = null, $force = false)
    {
        $this->_up($version);
    }

    /**
     * Rollback the database, either down to a specific version or as much as possible.
     *
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function down($version = null, $force = false)
    {
        $this->_down($version);
    }

    /**
     * Resets the database to the base version.
     *
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function reset($upVersion = null)
    {
        $this->_reset();
    }

    /**
     * Prepares the SQL for execution.
     *
     * @since [*next-version*]
     *
     * @param string $sql The SQL to execute.
     *
     * @return string The prepared SQL.
     */
    protected function _prepareSql($sql)
    {
        foreach ($this->formatters as $_key => $_formatter) {
            $_search  = sprintf('{%s}', $_key);
            $_replace = call_user_func_array($_formatter, [$this, $sql, $_search]);

            $sql = str_replace($_search, $_replace, $sql);
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getMigrationFilePatterns($direction = null)
    {
        $baseDir = implode(
            DIRECTORY_SEPARATOR,
            array_filter(
                [
                    $this->_folder,
                    static::MIGRATIONS_DIRECTORY,
                    $direction,
                ]
            )
        );

        return [
            $baseDir . DIRECTORY_SEPARATOR . '*%s.sql',
            $baseDir . DIRECTORY_SEPARATOR . '*%s-dev.sql',
        ];
    }

    /**
     * Creates a new "could not migrate" exception instance.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null $message  The error message, if any.
     * @param int|null               $code     The error code, if any.
     * @param RootException|null     $previous The inner exception for chaining, if any.
     * @param string|Stringable|null $version  The migration version that failed, if any.
     *
     * @return CouldNotMigrateExceptionInterface The created exception.
     */
    protected function _createCouldNotMigrateException(
        $message = null,
        $code = null,
        RootException $previous = null,
        $version = null
    ) {
        return new CouldNotMigrateException($message, $code, $previous, $this, $version);
    }

    /**
     * Creates a new migrator exception instance.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null $message  The error message, if any.
     * @param int|null               $code     The error code, if any.
     * @param RootException|null     $previous The inner exception for chaining, if any.
     *
     * @return MigratorExceptionInterface The created exception.
     */
    protected function _createMigratorException(
        $message = null,
        $code = null,
        RootException $previous = null
    ) {
        return new MigratorException($message, $code, $previous, $this);
    }
}
