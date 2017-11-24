<?php

namespace RebelCode\Migrations\TestStub;

use PDO;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
use PHPUnit_Extensions_Database_TestCase;
use Xpmock\TestCaseTrait;

abstract class BaseDatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    /*
     * Provides XpMock mocking and reflection functionality.
     *
     * @since [*next-version*]
     */
    use TestCaseTrait;

    /**
     * The PDO instance.
     *
     * @since [*next-version*]
     *
     * @var PDO
     */
    protected $pdo = null;

    /**
     * The DB connection.
     *
     * Should only be initialised once per test.
     *
     * @since [*next-version*]
     *
     * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected $connection = null;

    /**
     * Retrieves the database schema.
     *
     * @since [*next-version*]
     *
     * @return array An associative array with table names as keys and sub-arrays as values, such that
     *               each sub-array has column names as keys and another sub-array as value, containing
     *               the column info. Available keys in this column info array are "type" and "primary",
     *               mapping to a string type name and a boolean flag if a primary column, respectively.
     */
    abstract protected function _getDatabaseSchema();

    /**
     * Prepares the database by creating the tables and columns according to a schema.
     *
     * @since [*next-version*]
     *
     * @param array $schema The schema.
     */
    protected function _prepareDatabase(array $schema = [])
    {
        $pdo = $this->_getPdo();

        foreach ($schema as $_tableName => $_columns) {
            $_columnDefs = [];

            foreach ($_columns as $_columnName => $_columnMeta) {
                $_columnType = $_columnMeta['type'];
                $_columnPrimary = isset($_columnMeta['primary']) && boolval($_columnMeta['primary']);

                $_columnDefs[] = sprintf(
                    '%1$s %2$s %3$s',
                    $_columnName,
                    $_columnType,
                    $_columnPrimary ? 'PRIMARY KEY' : ''
                );
            }

            $_tableColumns = implode(', ', $_columnDefs);
            $_tableQuery = sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s)', $_tableName, $_tableColumns);

            $pdo->exec($_tableQuery);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createDefaultDBConnection(
                $this->_getPdo(),
                ':memory:'
            );

            $this->_prepareDatabase($this->_getDatabaseSchema());
        }

        return $this->connection;
    }

    /**
     * Retrieves the PDO instance.
     *
     * @since [*next-version*]
     *
     * @return PDO
     */
    protected function _getPdo()
    {
        if ($this->pdo == null) {
            $this->pdo = new PDO('sqlite::memory:');
        }

        return $this->pdo;
    }
}
