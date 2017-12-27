<?php

namespace RebelCode\Migrations\UnitTest;

use RebelCode\Migrations\AbstractDatabase;
use RebelCode\Migrations\MySqlDatabase as TestSubject;
use RebelCode\Migrations\TestStub\BaseDatabaseTestCase;
use RebelCode\Migrations\TestStub\PdoSqliteDriverStub;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class MySqlDatabaseTest extends BaseDatabaseTestCase
{
    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getDatabaseSchema()
    {
        return [
            'migration_log' => [
                'version' => ['type' => 'integer'],
                'status' => ['type' => 'text'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function getDataSet()
    {
        return $this->createArrayDataSet(
            [
                'migration_log' => [],
            ]
        );
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $subject = new TestSubject($driver, 'log');

        $this->assertInstanceOf(
            TestSubject::class,
            $subject,
            'A valid instance of the test subject could not be created.'
        );

        $this->assertInstanceOf(
            AbstractDatabase::class,
            $subject,
            'Test subject does not extend parent abstract class.'
        );
    }

    /**
     * Tests the constructor and the getters that correspond to the constructor arguments, to assert whether all data is
     * correctly set and retrieved.
     *
     * @since [*next-version*]
     */
    public function testConstructorGetters()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $table = uniqid('table-');
        $version = uniqid('version-');
        $status = uniqid('status-');
        $subject = new TestSubject($driver, $table, $version, $status);
        $reflect = $this->reflect($subject);

        $this->assertSame(
            $driver,
            $reflect->getDbDriver(),
            'Set and retrieved database drivers are not the same.'
        );
        $this->assertEquals(
            $table,
            $reflect->_getLogTableName(),
            'Set and retrieved table names do not match.'
        );
        $this->assertEquals(
            $version,
            $reflect->_getLogTableVersionColumn(),
            'Set and retrieved version columns do not match.'
        );
        $this->assertEquals(
            $status,
            $reflect->_getLogTableStatusColumn(),
            'Set and retrieved status columns do not match.'
        );
    }

    /**
     * Tests the log table getter and setter methods.
     *
     * @since [*next-version*]
     */
    public function testGetSetLogTableName()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $table = uniqid('table-');
        $subject = new TestSubject($driver, '');
        $reflect = $this->reflect($subject);

        $reflect->_setLogTableName($table);

        $this->assertEquals($table, $reflect->_getLogTableName(), 'Set and retrieved log table names do not match.');
    }

    /**
     * Tests the log table version column getter and setter methods.
     *
     * @since [*next-version*]
     */
    public function testGetSetLogTableVersionColumn()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $version = uniqid('version-');
        $subject = new TestSubject($driver, '');
        $reflect = $this->reflect($subject);

        $reflect->_setLogTableVersionColumn($version);

        $this->assertEquals(
            $version,
            $reflect->_getLogTableVersionColumn(),
            'Set and retrieved version columns do not match.'
        );
    }

    /**
     * Tests the log table status column getter and setter methods.
     *
     * @since [*next-version*]
     */
    public function testGetSetLogTableStatusColumn()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $status = uniqid('status-');
        $subject = new TestSubject($driver, '');
        $reflect = $this->reflect($subject);

        $reflect->_setLogTableStatusColumn($status);

        $this->assertEquals(
            $status,
            $reflect->_getLogTableStatusColumn(),
            'Set and retrieved status columns do not match.'
        );
    }

    /**
     * Tests the database name getter method to assert whether it's equal to the database name in the driver.
     *
     * @since [*next-version*]
     */
    public function testGetDatabaseName()
    {
        $dbName = 'migrations';
        $driver = new PdoSqliteDriverStub($this->_getPdo(), $dbName);
        $subject = new TestSubject($driver, '');
        $reflect = $this->reflect($subject);

        $this->assertEquals(
            $dbName,
            $reflect->_getDatabaseName(),
            'Driver database name and retrieved database name do not match'
        );
    }
}
