<?php

namespace RebelCode\Migrations\UnitTest;

use ByJG\AnyDataset\DbDriverInterface;
use PHPUnit_Framework_MockObject_MockObject;
use RebelCode\Migrations\AbstractDatabase as TestSubject;
use RebelCode\Migrations\TestStub\BaseDatabaseTestCase;
use RebelCode\Migrations\TestStub\PdoSqliteDriverStub;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class AbstractDatabaseTest extends BaseDatabaseTestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\Migrations\AbstractDatabase';

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
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @param array                  $methods The methods to mock.
     * @param DbDriverInterface|null $driver  The database driver.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function createInstance(array $methods = [], $driver = null)
    {
        $builder = $this->getMockBuilder(static::TEST_SUBJECT_CLASSNAME)
                        ->setMethods(
                            array_merge(
                                [
                                    'getDbDriver',
                                    '_getDatabaseName',
                                    '_getLogTable',
                                    '_getLogTableVersionColumn',
                                    '_getLogTableStatusColumn',
                                    '_normalizeString',
                                ],
                                $methods
                            )
                        )
                        ->disableOriginalConstructor();

        $mock = $builder->getMockForAbstractClass();

        $mock->method('getDbDriver')->willReturn($driver);
        $mock->method('_normalizeString')->willReturnCallback(
            function ($str) {
                return (string) $str;
            }
        );

        return $mock;
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = $this->createInstance();

        $this->assertInstanceOf(
            static::TEST_SUBJECT_CLASSNAME,
            $subject,
            'A valid instance of the test subject could not be created.'
        );
    }

    /**
     * Tests the SQL formatting function to assert whether it correctly replaces the status column name placeholder.
     *
     * @since [*next-version*]
     */
    public function testFormatSql()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);

        $subject->method('_getDatabaseName')->willReturn($db = uniqid('db-'));
        $subject->method('_getLogTableName')->willReturn($table = uniqid('table-'));
        $subject->method('_getLogTableVersionColumn')->willReturn($version = uniqid('version-'));
        $subject->method('_getLogTableStatusColumn')->willReturn($status = uniqid('status-'));

        $in = sprintf(
            'DB %1$s TABLE %2$s VERSION %3$s STATUS %4$s',
            TestSubject::PLACEHOLDER_DATABASE,
            TestSubject::PLACEHOLDER_LOG_TABLE,
            TestSubject::PLACEHOLDER_LOG_VERSION_COLUMN,
            TestSubject::PLACEHOLDER_LOG_STATUS_COLUMN
        );
        $out = $reflect->_formatSql($in);

        $this->assertNotContains(TestSubject::PLACEHOLDER_DATABASE, $out, 'Output has db placeholder');
        $this->assertNotContains(TestSubject::PLACEHOLDER_LOG_TABLE, $out, 'Output has table placeholder');
        $this->assertNotContains(TestSubject::PLACEHOLDER_LOG_VERSION_COLUMN, $out, 'Output has version placeholder');
        $this->assertNotContains(TestSubject::PLACEHOLDER_LOG_STATUS_COLUMN, $out, 'Output has status placeholder');
        $this->assertContains($db, $out, 'Output does not have db replacement');
        $this->assertContains($table, $out, 'Output does not have table replacement');
        $this->assertContains($version, $out, 'Output does not have version replacement');
        $this->assertContains($status, $out, 'Output does not have status replacement');
    }

    /**
     * Tests the SQL formatting function to assert whether it correctly replaces the status column name placeholder
     * when using the interpolation functionality.
     *
     * @since [*next-version*]
     */
    public function testFormatSqlInterpolate()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);

        $subject->method('_getDatabaseName')->willReturn($db = uniqid('db-'));
        $subject->method('_getLogTableName')->willReturn($table = uniqid('table-'));
        $subject->method('_getLogTableVersionColumn')->willReturn($version = uniqid('version-'));
        $subject->method('_getLogTableStatusColumn')->willReturn($status = uniqid('status-'));

        $in = 'DB %1$s TABLE %2$s VERSION %3$s STATUS %4$s';
        $out = $reflect->_formatSql(
            $in,
            [
                TestSubject::PLACEHOLDER_DATABASE,
                TestSubject::PLACEHOLDER_LOG_TABLE,
                TestSubject::PLACEHOLDER_LOG_VERSION_COLUMN,
                TestSubject::PLACEHOLDER_LOG_STATUS_COLUMN,
            ]
        );

        $this->assertNotContains('%1$s', $out, 'Output has db placeholder.');
        $this->assertNotContains('%2$s', $out, 'Output has table placeholder.');
        $this->assertNotContains('%3$s', $out, 'Output has version placeholder.');
        $this->assertNotContains('%4$s', $out, 'Output has status placeholder.');
        $this->assertContains($db, $out, 'Output does not have db replacement');
        $this->assertContains($table, $out, 'Output does not have table replacement');
        $this->assertContains($version, $out, 'Output does not have version replacement');
        $this->assertContains($status, $out, 'Output does not have status replacement');
    }

    /**
     * Tests the version getter when the database is un-versioned to assert whether an exception is thrown.
     *
     * @since [*next-version*]
     */
    public function testGetVersionUnversioned()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), 'migrations');
        $subject = $this->createInstance([], $driver);

        $this->setExpectedException('ByJG\DbMigration\Exception\DatabaseNotVersionedException');

        $subject->getVersion();
    }

    /**
     * Tests the version getter after initializing the database to assert whether the retrieved version is correct.
     *
     * @since [*next-version*]
     */
    public function testCheckGetVersion()
    {
        $dbName = 'migrations';
        $table = 'migration_log';
        $driver = new PdoSqliteDriverStub($this->_getPdo(), $dbName);
        $subject = $this->createInstance([], $driver);
        $reflect = $this->reflect($subject);

        // DB, table and column names
        $subject->method('_getDatabaseName')->willReturn($dbName);
        $subject->method('_getLogTableName')->willReturn($table);
        $subject->method('_getLogTableVersionColumn')->willReturn('version');
        $subject->method('_getLogTableStatusColumn')->willReturn('status');

        $reflect->checkExistsVersion();

        $this->assertEquals(
            [
                'version' => 0,
                'status' => TestSubject::STATUS_UNKNOWN,
            ],
            $subject->getVersion(),
            'Retrieved version info is incorrect.'
        );
    }

    /**
     * Tests the version getter and setter methods to assert whether the version info is correctly set and retrieved.
     *
     * @since [*next-version*]
     */
    public function testGetSetVersion()
    {
        $dbName = 'migrations';
        $table = 'migration_log';
        $driver = new PdoSqliteDriverStub($this->_getPdo(), $dbName);
        $subject = $this->createInstance([], $driver);
        $reflect = $this->reflect($subject);

        // DB, table and column names
        $subject->method('_getDatabaseName')->willReturn($dbName);
        $subject->method('_getLogTableName')->willReturn($table);
        $subject->method('_getLogTableVersionColumn')->willReturn('version');
        $subject->method('_getLogTableStatusColumn')->willReturn('status');

        $reflect->checkExistsVersion();
        $reflect->setVersion($version = rand(1, 10), $status = uniqid('status-'));

        $this->assertEquals(
            [
                'version' => $version,
                'status' => $status,
            ],
            $subject->getVersion(),
            'Set and retrieved version info do not match.'
        );
    }

    /**
     * Tests the version getter and setter methods to assert whether the version info is correctly set and retrieved.
     *
     * @since [*next-version*]
     */
    public function testGetSetUpdateVersion()
    {
        $dbName = 'migrations';
        $table = 'migration_log';
        $driver = new PdoSqliteDriverStub($this->_getPdo(), $dbName);
        $subject = $this->createInstance([], $driver);
        $reflect = $this->reflect($subject);

        // DB, table and column names
        $subject->method('_getDatabaseName')->willReturn($dbName);
        $subject->method('_getLogTableName')->willReturn($table);
        $subject->method('_getLogTableVersionColumn')->willReturn('version');
        $subject->method('_getLogTableStatusColumn')->willReturn('status');

        $reflect->checkExistsVersion();
        $reflect->setVersion($version = rand(1, 10), $status = uniqid('status-'));
        $reflect->updateVersionTable();

        $this->assertEquals(
            [
                'version' => $version,
                'status' => TestSubject::STATUS_UNKNOWN,
            ],
            $subject->getVersion(),
            'Set and retrieved version info do not match.'
        );
    }
}
