<?php

namespace RebelCode\Migrations;

use ByJG\DbMigration\Exception\DatabaseIsIncompleteException;
use ByJG\DbMigration\Exception\DatabaseNotVersionedException;
use ByJG\DbMigration\Migration as ByjgMigration;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception as RootException;
use InvalidArgumentException;
use RebelCode\Migrations\Exception\CouldNotMigrateExceptionInterface;

/**
 * Abstract functionality for migrations
 *
 * @since [*next-version*]
 */
abstract class AbstractMigrator extends ByjgMigration
{
    /**
     * Constant for "up" direction migration.
     *
     * @since [*next-version*]'
     */
    const MIGRATION_DIRECTION_UP = 'up';

    /**
     * Constant for "down" direction migration.
     *
     * @since [*next-version*]
     */
    const MIGRATION_DIRECTION_DOWN = 'down';

    /**
     * {@inheritdoc}
     *
     * Overridden to wrap around the parent method and re-throw any exceptions as specific exceptions.
     *
     * @since [*next-version*]
     */
    protected function _up($upVersion = null, $force = false)
    {
        try {
            parent::up($upVersion, $force);
        } catch (RootException $exception) {
            throw $this->_createCouldNotMigrateException(
                $this->__('Failed to migrate up'),
                null,
                $exception,
                $upVersion
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * Overridden to wrap around the parent method and re-throw any exceptions as specific exceptions.
     *
     * @since [*next-version*]
     */
    protected function _down($upVersion = null, $force = false)
    {
        try {
            parent::down($upVersion, $force);
        } catch (RootException $exception) {
            throw $this->_createCouldNotMigrateException(
                $this->__('Failed to migrate down'),
                null,
                $exception,
                $upVersion
            );
        }
    }

    /**
     * Resets the database to the base version, and then optionally up to a specific version.
     *
     * Almost identical to {@link ByjgMigration::reset()}, except that it catches and throws
     * specific exceptions and does not attempt to perform "up" migration after resetting.
     *
     * @since [*next-version*]
     */
    protected function _reset()
    {
        try {
            try {
                $versionInfo    = $this->getCurrentVersion();
                $currentVersion = intval($versionInfo['version']);
            } catch (DatabaseNotVersionedException $versionedException) {
                $currentVersion = 0;
            }

            if ($this->_callableProgress) {
                call_user_func_array($this->_callableProgress, ['reset', $currentVersion, 0]);
            }

            $this->getDbCommand()->dropDatabase();
            $this->getDbCommand()->createDatabase();
            $this->getDbCommand()->createVersion();
            $this->getDbCommand()->executeSql(file_get_contents($this->getBaseSql()));
            $this->getDbCommand()->setVersion(0, 'complete');
        } catch (RootException $exception) {
            throw $this->_createCouldNotMigrateException(
                $this->__('Failed to reset'),
                null,
                $exception,
                0
            );
        }
    }

    /**
     * Method for execute the migration.
     *
     * Overridden to:
     * * pass different arguments to the callback progress function
     * * allow preparation of SQL query strings before execution
     * * contain a portion of the fix for the SQL file execution bug See {@link getMigrationSqlQuery()}.
     * * throw specific exceptions
     *
     * @param int  $upVersion
     * @param int  $increment Can accept 1 for UP or -1 for down
     * @param bool $force
     *
     * @throws DatabaseIsIncompleteException
     */
    protected function migrate($upVersion, $increment, $force)
    {
        $versionInfo    = $this->getCurrentVersion();
        $currentVersion = intval($versionInfo['version']);

        if (strpos($versionInfo['status'], 'partial') !== false && !$force) {
            throw new DatabaseIsIncompleteException(
                'Database was not fully updated - use the "force" argument to ignore this error'
            );
        }

        while (
            $this->canContinue($currentVersion, $upVersion, $increment)
            &&
            $rawSql = $this->getMigrationSqlQuery($currentVersion, $increment)
        ) {
            $nextVersion = $currentVersion + $increment;

            if (is_callable($this->_callableProgress)) {
                call_user_func_array(
                    $this->_callableProgress,
                    ['migrate', $currentVersion, $nextVersion]
                );
            }

            $preparedSql = $this->_prepareSql($rawSql);

            $this->getDbCommand()->setVersion($nextVersion, 'partial ' . ($increment > 0 ? 'up' : 'down'));
            $this->getDbCommand()->executeSql($preparedSql);
            $this->getDbCommand()->setVersion($nextVersion, 'complete');

            $currentVersion = $nextVersion;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Overridden to contain a portion of the fix for the SQL file execution bug. See {@link getMigrationSqlQuery()}.
     * This method now properly checks if a version lies in a sequence between the current version
     * and the version to migrate to, including/excluding the current version depending on the increment.
     *
     * @since [*next-version*]
     */
    protected function canContinue($currentVersion, $upVersion, $increment)
    {
        // Difference between versions
        $delta = (intval($upVersion) - intval($currentVersion)) * $increment;

        if ($increment < 0) {
            return ($delta > 0);
        }

        return ($delta > 0 || $upVersion === null);
    }

    /**
     * Retrieves the SQL query string for a particular version and increment (direction).
     *
     * When migrating up from version `x` to any other version more recent than `x`, the SQL query returned is the "up"
     * migration SQL for version `x` + 1.
     *
     * When migrating down from version `x` to any other version older than `x`, the SQL query returned is the "down"
     * migration SQL for version `x`.
     *
     * Every version's up and down migration SQL queries are treated as commit-style changes. Invoking `x` up will
     * apply the changes named `x` to the database. Conversely, invoking `x` down will undo those changes.
     *
     * @since [*next-version*]
     *
     * @param int $version   The version.
     * @param int $increment The increment.
     *
     * @return null|string The SQL, or null if no SQL was found for the given version.
     */
    protected function getMigrationSqlQuery($version, $increment)
    {
        $version = $this->_normalizeInt($version);
        $fileNum = ($increment >= 0)
            ? $version + 1
            : $version;

        $file = $this->getMigrationSql($fileNum, $increment);

        return file_exists($file) && is_readable($file)
            ? file_get_contents($file)
            : null;
    }

    /**
     * Retrieves the path to an SQL file.
     *
     * Overridden to use the new directory customization.
     *
     * @since [*next-version*]
     *
     * @param int|string $version   The version.
     * @param int        $increment The increment.
     *
     * @return string|null The path to the SQL file or null if no matching file was found.
     */
    public function getMigrationSql($version, $increment)
    {
        $version = $this->_normalizeInt($version);
        $files   = $this->_getMigrationFiles($version, $increment);
        $count   = count($files);

        if ($count > 1) {
            $this->_createCouldNotMigrateException(
                $this->__('Found multiple migration files with the same version number'),
                null,
                null,
                $version
            );
        }

        return $count
            ? reset($files)
            : null;
    }

    /**
     * Retrieves the migration files that match the given version and increment.
     *
     * @since [*next-version*]
     *
     * @param int $version   The migration version.
     * @param int $increment The increment, either 1 or -1.
     *
     * @return string[] The matched migration file paths.
     */
    protected function _getMigrationFiles($version, $increment)
    {
        $results   = [];
        $direction = ($increment < 0)
            ? static::MIGRATION_DIRECTION_DOWN
            : static::MIGRATION_DIRECTION_UP;

        $patterns = $this->_getMigrationFilePatterns($direction);

        foreach ($patterns as $_pattern) {
            $_glob   = sprintf($_pattern, $version);
            $_files  = glob($_glob);
            $results = array_merge($results, $_files);
        }

        return $results;
    }

    /**
     * Retrieves the glob-style patterns for finding migration files.
     *
     * The file name portion of each pattern may contain a printf-style placeholder, which will be interpolated into
     * the migration version number.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null $direction The direction of the migration. See the MIGRATION_DIRECTION_* constants.
     *
     * @return string[] Glob-style patterns for the absolute path where migration files are found.
     */
    abstract protected function _getMigrationFilePatterns($direction = null);

    /**
     * Prepares the SQL for execution.
     *
     * @since [*next-version*]
     *
     * @param string $sql The SQL to execute.
     *
     * @return string The prepared SQL.
     */
    abstract protected function _prepareSql($sql);

    /**
     * Normalizes a value into an integer.
     *
     * The value must be a whole number, or a string representing such a number,
     * or an object representing such a string.
     *
     * @since [*next-version*]
     *
     * @param String|stringable|float|int $value The value to normalize.
     *
     * @throws InvalidArgumentException If value cannot be normalized.
     *
     * @return int The normalized value.
     */
    abstract protected function _normalizeInt($value);

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
    abstract protected function _createCouldNotMigrateException(
        $message = null,
        $code = null,
        RootException $previous = null,
        $version = null
    );

    /**
     * Translates a string, and replaces placeholders.
     *
     * @since [*next-version*]
     * @see   sprintf()
     *
     * @param string $string  The format string to translate.
     * @param array  $args    Placeholder values to replace in the string.
     * @param mixed  $context The context for translation.
     *
     * @return string The translated string.
     */
    abstract protected function __($string, $args = [], $context = null);
}
