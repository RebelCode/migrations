<?php

namespace RebelCode\Migrations;

use ByJG\DbMigration\Database\AbstractDatabase as ByjgAbstractDatabase;
use ByJG\DbMigration\Exception\DatabaseNotVersionedException;
use ByJG\DbMigration\Exception\OldVersionSchemaException;
use Dhii\Util\String\StringableInterface as Stringable;
use InvalidArgumentException;

/**
 * Abstract functionality for databases.
 *
 * Extends Byjg's class to add customizable table and column names.
 *
 * @since [*next-version*]
 */
abstract class AbstractDatabase extends ByjgAbstractDatabase
{
    /**
     * The placeholder to use for the database name.
     *
     * @since [*next-version*]
     */
    const PLACEHOLDER_DATABASE = '{db}';

    /**
     * The placeholder to use for the version log table name.
     *
     * @since [*next-version*]
     */
    const PLACEHOLDER_LOG_TABLE = '{lt}';

    /**
     * The placeholder to use for the version column name in the version log table.
     *
     * @since [*next-version*]
     */
    const PLACEHOLDER_LOG_VERSION_COLUMN = '{lt_version}';

    /**
     * The placeholder to use for the status column name in the version log table.
     *
     * @since [*next-version*]
     */
    const PLACEHOLDER_LOG_STATUS_COLUMN = '{lt_status}';

    /**
     * Formats a query string.
     *
     * @since [*next-version*]
     *
     * @param string $sql The SQL query string to format.
     *
     * @return string The formatted SQL query.
     */
    protected function _formatSql($sql)
    {
        $sql = $this->_normalizeString($sql);
        $sql = str_replace(static::PLACEHOLDER_DATABASE, $this->_getDatabaseName(), $sql);
        $sql = str_replace(static::PLACEHOLDER_LOG_TABLE, $this->_getLogTableName(), $sql);
        $sql = str_replace(static::PLACEHOLDER_LOG_VERSION_COLUMN, $this->_getLogTableVersionColumn(), $sql);
        $sql = str_replace(static::PLACEHOLDER_LOG_STATUS_COLUMN, $this->_getLogTableStatusColumn(), $sql);

        return $sql;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getVersion()
    {
        $result = [];

        try {
            $result['version'] = $this->getDbDriver()->getScalar(
                $this->_formatSql('SELECT {lt_version} FROM {lt}')
            );
        } catch (\Exception $ex) {
            throw new DatabaseNotVersionedException(
                'This database does not have a migration version. Please use "migrate reset" or "migrate install" to create one.'
            );
        }

        try {
            $result['status'] = $this->getDbDriver()->getScalar(
                $this->_formatSql('SELECT {lt_status} FROM {lt}')
            );
        } catch (\Exception $ex) {
            throw new OldVersionSchemaException(
                'This database does not have a migration version. Please use "migrate install" for update it.'
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function setVersion($version, $status)
    {
        $this->getDbDriver()->execute(
            $this->_formatSql('UPDATE {lt} SET {lt_version} = :version, {lt_status} = :status'),
            [
                'version' => $version,
                'status'  => $status,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function checkExistsVersion()
    {
        // Get the version to check if exists
        $versionInfo = $this->getVersion();
        if (empty($versionInfo['version'])) {
            $this->getDbDriver()->execute(
                $this->_formatSql('INSERT INTO {lt} VALUES(0, \'unknown\')')
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function updateVersionTable()
    {
        $currentVersion = $this->getDbDriver()->getScalar($this->_formatSql('SELECT {lt_version} FROM {lt}'));
        $this->getDbDriver()->execute($this->_formatSql('DROP TABLE {lt}'));
        $this->createVersion();
        $this->setVersion($currentVersion, 'unknown');
    }

    /**
     * Retrieves the name of the database.
     *
     * @since [*next-version*]
     *
     * @return string The name of the database.
     */
    abstract protected function _getDatabaseName();

    /**
     * Retrieves the name of the table where migration are logged.
     *
     * @since [*next-version*]
     *
     * @return string The name of the version table.
     */
    abstract protected function _getLogTableName();

    /**
     * Retrieves the name of the version column in the version log table.
     *
     * @since [*next-version*]
     *
     * @return string The name of the version column.
     */
    abstract protected function _getLogTableVersionColumn();

    /**
     * Retrieves the name of the status column in the version log table.
     *
     * @since [*next-version*]
     *
     * @return string The name of the status column.
     */
    abstract protected function _getLogTableStatusColumn();

    /**
     * Normalizes a value to its string representation.
     *
     * The values that can be normalized are any scalar values, as well as
     * {@see StringableInterface).
     *
     * @since [*next-version*]
     *
     * @param Stringable|string|int|float|bool $subject The value to normalize to string.
     *
     * @throws InvalidArgumentException If the value cannot be normalized.
     *
     * @return string The string that resulted from normalization.
     */
    abstract protected function _normalizeString($subject);
}
