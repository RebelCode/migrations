<?php

namespace RebelCode\Migrations;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Factory;
use ByJG\Util\Uri;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Psr\Http\Message\UriInterface;

/**
 * A MySql database adapter implementation with customizable log table name and column names.
 *
 * @since [*next-version*]
 */
class MySqlDatabase extends AbstractDatabase
{
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
     * The default name of the version column in the log table.
     *
     * @since [*next-version*]
     */
    const DEFAULT_LOG_VERSION_COLUMN = 'version';

    /**
     * The default name of the status column in the log table.
     *
     * @since [*next-version*]
     */
    const DEFAULT_LOG_STATUS_COLUMN = 'status';

    /**
     * The database name.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $dbName;

    /**
     * The name of the table where migration versions are logged.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $logTable;

    /**
     * The name of the version column in the log table.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $versionColumn;

    /**
     * The name of the status column in the log table.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $statusColumn;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param DbDriverInterface $dbDriver
     * @param                   $logTable
     * @param                   $versionColumn
     * @param                   $statusColumn
     */
    public function __construct(
        DbDriverInterface $dbDriver,
        $logTable,
        $versionColumn = self::DEFAULT_LOG_VERSION_COLUMN,
        $statusColumn = self::DEFAULT_LOG_STATUS_COLUMN
    ) {
        parent::__construct($dbDriver);

        $this->_setLogTableName($logTable);
        $this->_setLogTableVersionColumn($versionColumn);
        $this->_setLogTableStatusColumn($statusColumn);
        $this->_setDatabaseName(static::getDatabaseNameFromUri($dbDriver->getUri()));
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getDatabaseName()
    {
        return $this->dbName;
    }

    /**
     * Sets the database name.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $dbName
     */
    protected function _setDatabaseName($dbName)
    {
        $this->dbName = $this->_normalizeString($dbName);
    }

    /**
     * Retrieves the name of the table where migration versions are logged.
     *
     * @since [*next-version*]
     *
     * @return string|Stringable The name of the version table.
     */
    protected function _getLogTableName()
    {
        return $this->logTable;
    }

    /**
     * Sets the name of the table where migration versions are logged.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $logTable The name of the version table.
     */
    protected function _setLogTableName($logTable)
    {
        $this->logTable = $this->_normalizeString($logTable);
    }

    /**
     * Retrieves the name of the version column in the log table.
     *
     * @since [*next-version*]
     *
     * @return string The name of the version column in the log table.
     */
    protected function _getLogTableVersionColumn()
    {
        return $this->versionColumn;
    }

    /**
     * Sets the name of the version column in the log table.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $versionColumn The name of the version column in the log table.
     */
    protected function _setLogTableVersionColumn($versionColumn)
    {
        $this->versionColumn = $this->_normalizeString($versionColumn);
    }

    /**
     * Retrieves the name of the version column in the log table.
     *
     * @since [*next-version*]
     *
     * @return string The name of the version column in the log table.
     */
    protected function _getLogTableStatusColumn()
    {
        return $this->statusColumn;
    }

    /**
     * Sets the name of the version column in the log table.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $statusColumn The name of the version column in the log table.
     */
    protected function _setLogTableStatusColumn($statusColumn)
    {
        $this->statusColumn = $this->_normalizeString($statusColumn);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function executeSql($sql)
    {
        $this->getDbDriver()->execute($sql);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function createDatabase()
    {
        $this->executeSql(
            $this->_formatSql('CREATE SCHEMA IF NOT EXISTS `{db}` DEFAULT CHARACTER SET utf8 ;')
        );
        $this->executeSql(
            $this->_formatSql('USE `{db}`')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function dropDatabase()
    {
        $this->executeSql(
            $this->_formatSql('DROP DATABASE `{db}`')
        );
    }

    /**
     * Retrieves the name of the database in a given URI.
     *
     * @since [*next-version*]
     *
     * @param UriInterface $uri The URI.
     *
     * @return string The extracted name of the database.
     */
    public static function getDatabaseNameFromUri(UriInterface $uri)
    {
        return preg_replace('~^/~', '', $uri->getPath());
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public static function prepareEnvironment(Uri $uri)
    {
        $customUri = new Uri($uri->__toString());
        $database  = static::getDatabaseNameFromUri($customUri);
        $dbDriver  = Factory::getDbRelationalInstance($customUri->withPath('/')->__toString());
        $dbDriver->execute(
            sprintf('CREATE SCHEMA IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8 ;', $database)
        );
    }
}
