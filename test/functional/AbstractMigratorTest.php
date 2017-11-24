<?php

namespace RebelCode\Migrations\FuncTest;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\DbMigration\Database\DatabaseInterface;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_MockObject_MockObject;
use RebelCode\Migrations\AbstractDatabase;
use RebelCode\Migrations\AbstractMigrator;
use RebelCode\Migrations\TestStub\BaseDatabaseTestCase;
use RebelCode\Migrations\TestStub\PdoSqliteDriverStub;

/**
 * Tests {@see RebelCode\Migrations\AbstractMigrator}.
 *
 * @since [*next-version*]
 */
class AbstractMigratorTest extends BaseDatabaseTestCase
{
    /**
     * The name of the mocked database.
     *
     * @since [*next-version*]
     */
    const DB_NAME = 'migrations';

    /**
     * The name of the log migration table in the mocked database.
     *
     * @since [*next-version*]
     */
    const TABLE_NAME = 'log';

    /**
     * The name of the version column in the log migration table.
     *
     * @since [*next-version*]
     */
    const VERSION_COL = 'version';

    /**
     * The name of the status column in the log migration table.
     *
     * @since [*next-version*]
     */
    const STATUS_COL = 'status';

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function _getDatabaseSchema()
    {
        return [
            static::TABLE_NAME => [
                static::VERSION_COL => ['type' => 'integer'],
                static::STATUS_COL => ['type' => 'text'],
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
        return $this->createArrayDataSet([]);
    }

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @param array                  $mockMethods Additional methods to mock.
     * @param DatabaseInterface|null $database    The database.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function createInstance(array $mockMethods = [], $database = null)
    {
        $mock = $this->getMockBuilder(AbstractMigrator::class)
                     ->setMethods(
                         array_merge(
                             $mockMethods,
                             [
                                 'getDbCommand',
                                 '__',
                                 '_normalizeInt',
                                 '_normalizeString',
                                 '_prepareSql',
                                 '_getMigrationFilePattern',
                                 '_createCouldNotMigrateException',
                             ]
                         )
                     )
                     ->disableOriginalConstructor()
                     ->getMockForAbstractClass();

        $mock->method('getDbCommand')->willReturn($database);
        $mock->method('__')->willReturnArgument(0);
        $mock->method('_prepareSql')->willReturnArgument(0);
        $mock->method('_normalizeInt')->willReturnArgument(0);
        $mock->method('_normalizeString')->willReturnArgument(0);
        $mock->method('_createCouldNotMigrateException')->willReturnCallback(
            function ($m, $c, $p) {
                return new Exception($m, $c, $p);
            }
        );

        return $mock;
    }

    /**
     * Creates a new mocked database for testing.
     *
     * @since [*next-version*]
     *
     * @param DbDriverInterface $driver The db driver.
     *
     * @return DatabaseInterface The created database.
     */
    public function createDatabase($driver = null)
    {
        $builder = $this->getMockBuilder(AbstractDatabase::class)
                        ->setMethods(
                            [
                                'getDbDriver',
                                'executeSql',
                                '_getDatabaseName',
                                '_getLogTableName',
                                '_getLogTableVersionColumn',
                                '_getLogTableStatusColumn',
                                '_normalizeString',
                            ]
                        )
                        ->disableOriginalConstructor();

        $mock = $builder->getMockForAbstractClass();
        $mock->method('getDbDriver')->willReturn($driver);
        $mock->method('_getDatabaseName')->willReturn(static::DB_NAME);
        $mock->method('_getLogTableName')->willReturn(static::TABLE_NAME);
        $mock->method('_getLogTableVersionColumn')->willReturn(static::VERSION_COL);
        $mock->method('_getLogTableStatusColumn')->willReturn(static::STATUS_COL);
        $mock->method('_normalizeString')->willReturnArgument(0);
        $mock->method('executeSql')->willReturnCallback(
            function ($sql) use ($driver) {
                if ($driver !== null) {
                    $driver->execute($sql);
                }
            }
        );

        return $mock;
    }

    /**
     * Creates a virtual file system.
     *
     * @since [*next-version*]
     *
     * @param array $structure The structure of the virtual file system.
     *
     * @return vfsStreamDirectory The file system.
     */
    public function createFileSystem(array $structure)
    {
        $vfs = vfsStream::setup('root', null, $structure);

        return $vfs;
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a positive increment
     * to a larger version number is allowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueUp()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 5);
        $upVersion = rand(6, 10);
        $increment = 1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a positive increment
     * to a smaller version number is disallowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueUpFail()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(6, 10);
        $upVersion = rand(1, 5);
        $increment = 1;

        $this->assertFalse($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a positive increment
     * to the same version number is disallowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueUpEqual()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion = $currVersion;
        $increment = 1;

        $this->assertFalse($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a positive increment
     * to an unknown up version is allowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueUpNull()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion = null;
        $increment = 1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a negative increment
     * to a larger version number is allowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueDown()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(6, 10);
        $upVersion = rand(1, 5);
        $increment = -1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a negative increment
     * to a smaller version number is disallowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueDownFail()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 5);
        $upVersion = rand(6, 10);
        $increment = -1;

        $this->assertFalse($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a negative increment
     * to the same version number is disallowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueDownEqual()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion = $currVersion;
        $increment = -1;

        $this->assertFalse($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the canContinue method to assert whether it correctly determines that migration with a negative increment
     * to an unknown up version is allowed.
     *
     * @since [*next-version*]
     */
    public function testCanContinueDownNull()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion = null;
        $increment = -1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the matching files method to ensure that it correctly matches files in a directory with a regex pattern.
     *
     * @since [*next-version*]
     */
    public function testGetMatchingFiles()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $pattern = '/foo[0-9]/'; // match all "foo" with a number
        $fileSystem = $this->createFileSystem(
            [
                $f1 = uniqid('foo-') => '',
                $f2 = uniqid('foo1-') => '',
                $f3 = uniqid('foo8-') => '',
                $f4 = uniqid('bar-') => '',
                $f5 = uniqid('baz-') => '',
            ]
        );

        $files = $reflect->_getMatchingFiles($fileSystem->url(), $pattern);

        $this->assertCount(2, $files, 'Expected number of matched files is incorrect.');
        $this->assertContains(
            $fileSystem->url().DIRECTORY_SEPARATOR.$f2,
            $files,
            'Match files do not contain expected file.'
        );
        $this->assertContains(
            $fileSystem->url().DIRECTORY_SEPARATOR.$f3,
            $files,
            'Match files do not contain expected file.'
        );
    }

    /**
     * Tests the migration files getter method with a direction of "up".
     *
     * @since [*next-version*]
     */
    public function testGetMigrationFilesUp()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $version = 2;
        $increment = 1;
        $direction = 'up';
        $structure = [
            'migrations' => [
                'up-1.sql' => uniqid('up-1-'),
                'up-2.sql' => uniqid('up-2-'),
                'up-3.sql' => uniqid('up-3-'),
                'down-1.sql' => uniqid('down-1-'),
                'down-2.sql' => uniqid('down-2-'),
                'down-3.sql' => uniqid('down-3-'),
            ],
        ];
        $fileSystem = $this->createFileSystem($structure);

        $subject->method('_getMigrationFilePatterns')
                ->with($direction)
                ->willReturn(
                    [
                        $fileSystem->url().'/migrations' => '/^up-%d\.sql$/',
                    ]
                );

        $files = $reflect->_getMigrationFiles($version, $increment);
        $files = array_map('basename', $files);

        $this->assertCount(1, $files, 'Resulting file list contains more than one entry.');
        $this->assertContains('up-2.sql', $files, 'The expected file is not in the resulting file list.');
    }

    /**
     * Tests the migration files getter method with a direction of "down".
     *
     * @since [*next-version*]
     */
    public function testGetMigrationFilesDown()
    {
        $subject = $this->createInstance();
        $reflect = $this->reflect($subject);
        $version = 3;
        $increment = -1;
        $direction = 'down';
        $structure = [
            'migrations' => [
                'up-1.sql' => uniqid('up-1-'),
                'up-2.sql' => uniqid('up-2-'),
                'up-3.sql' => uniqid('up-3-'),
                'down-1.sql' => uniqid('down-1-'),
                'down-2.sql' => uniqid('down-2-'),
                'down-3.sql' => uniqid('down-3-'),
            ],
        ];
        $fileSystem = $this->createFileSystem($structure);

        $subject->method('_getMigrationFilePatterns')
                ->with($direction)
                ->willReturn(
                    [
                        $fileSystem->url().'/migrations' => '/^down-%d\.sql$/',
                    ]
                );

        $files = $reflect->_getMigrationFiles($version, $increment);
        $files = array_map('basename', $files);

        $this->assertCount(1, $files, 'Resulting file list contains more than one entry.');
        $this->assertContains('down-3.sql', $files, 'The expected file is not in the resulting file list.');
    }

    /**
     * Tests the migration SQL file getter method to assert whether it correctly retrieves the single SQL file.
     *
     * @since [*next-version*]
     */
    public function testGetMigrationSql()
    {
        $subject = $this->createInstance(['_getMigrationFiles']);
        $reflect = $this->reflect($subject);
        $file = uniqid('file-');

        $subject->method('_getMigrationFiles')
                ->willReturn([$file]);

        $this->assertEquals(
            $file,
            // Version and increment do not matter
            $reflect->getMigrationSql(null, null),
            'Expected and retrieved migration file are not the same.'
        );
    }

    /**
     * Tests the migration SQL file getter method to assert whether it correctly throws when multiple SQL files are
     * found.
     *
     * @since [*next-version*]
     */
    public function testGetMigrationSqlMultipleFiles()
    {
        $subject = $this->createInstance(['_getMigrationFiles']);
        $reflect = $this->reflect($subject);
        $file1 = uniqid('file-');
        $file2 = uniqid('file-');

        $subject->method('_getMigrationFiles')
                ->willReturn([$file1, $file2]);

        $this->setExpectedException('Exception');

        // Version and increment do not matter
        $reflect->_getMigrationSql(null, null);
    }

    /**
     * Tests the migration SQL file getter method to assert whether it correctly returns null when no file was found.
     *
     * @since [*next-version*]
     */
    public function testGetMigrationSqlNoFile()
    {
        $subject = $this->createInstance(['_getMigrationFiles']);
        $reflect = $this->reflect($subject);

        $subject->method('_getMigrationFiles')
                ->willReturn([]);

        // Version and increment do not matter
        $this->assertNull($reflect->getMigrationSql(null, null), 'Expected null.');
    }

    /**
     * Tests the migration SQL query getter to assert whether it correctly reads the SQL contents of a file.
     *
     * @since [*next-version*]
     */
    public function testGetMigrationSqlQuery()
    {
        $subject = $this->createInstance(['getMigrationSql']);
        $reflect = $this->reflect($subject);
        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'file.sql' => $expected = uniqid('sql-'),
                ],
            ]
        );

        $subject->method('getMigrationSql')->willReturn($fileSystem->url().'/sql/file.sql');

        $result = $reflect->_getMigrationSqlQuery(null, null);

        $this->assertEquals($expected, $result, 'Retrieved and expected contents are not the same.');
    }

    /**
     * Tests the migration reset to assert whether the version is correctly set to zero.
     *
     * @since [*next-version*]
     */
    public function testReset()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance([], $database);
        $reflect = $this->reflect($subject);
        $expected = [
            static::VERSION_COL => 0,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Expected and retrieved versions do not match.');
    }

    /**
     * Tests the migration up procedure to assert whether the version is correctly incremented and the SQL invoked.
     *
     * @since [*next-version*]
     */
    public function testMigrateUp()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance(['_getMigrationFilePatterns'], $database);
        $reflect = $this->reflect($subject);

        $mTable = 'test_table';
        $mTableCol = 'id';
        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'up' => [
                        '1.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s int)', $mTable, $mTableCol),
                    ],
                ],
            ]
        );

        $subject->method('_getMigrationFilePatterns')
                ->willReturn(
                    [
                        $fileSystem->url().'/sql/up/' => '/^%d\.sql$/',
                    ]
                );

        $expected = [
            static::VERSION_COL => 1,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();
        $reflect->_up();

        $tables = $this->getConnection()->getMetaData()->getTableNames();
        $columns = $this->getConnection()->getMetaData()->getTableColumns($mTable);

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Incorrect database migration version.');
        $this->assertContains($mTable, $tables, 'Database does not have the new table.');
        $this->assertEquals([$mTableCol], $columns, 'Table created by the migration does not have the column.');
    }

    /**
     * Tests the migration up procedure to assert whether the version is correctly incremented and the SQL invoked.
     *
     * @since [*next-version*]
     */
    public function testMigrateUpMultiple()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance(['_getMigrationFilePatterns'], $database);
        $reflect = $this->reflect($subject);

        $mTable = 'test_table';
        $mTable1Col1 = 'id';
        $mTable1Col2 = 'name';
        $mTable2 = 'test_table2';
        $mTable2Col1 = 'id';
        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'up' => [
                        '1.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s int)', $mTable, $mTable1Col1),
                        '2.sql' => sprintf('ALTER TABLE %1$s ADD %2$s int', $mTable, $mTable1Col2),
                        '3.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s int)', $mTable2, $mTable2Col1),
                    ],
                ],
            ]
        );

        $subject->method('_getMigrationFilePatterns')
                ->willReturn(
                    [
                        $fileSystem->url().'/sql/up/' => '/^%d\.sql$/',
                    ]
                );

        $expected = [
            static::VERSION_COL => 3,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();
        $reflect->_up();

        $tables = $this->getConnection()->getMetaData()->getTableNames();
        $columns1 = $this->getConnection()->getMetaData()->getTableColumns($mTable);
        $columns2 = $this->getConnection()->getMetaData()->getTableColumns($mTable2);

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Incorrect database migration version.');

        $this->assertContains($mTable, $tables, 'Table added by migration 1 not found in database.');
        $this->assertEquals(
            [$mTable1Col1, $mTable1Col2],
            $columns1,
            'Incorrect columns for table created in migration #1 and modified in migration #2.'
        );

        $this->assertContains($mTable2, $tables, 'Table added by migration 3 not found in database.');
        $this->assertEquals([$mTable2Col1], $columns2, 'Incorrect columns for table created in migration #2.');
    }

    /**
     * Tests the migration up procedure to assert whether the version is correctly incremented and the SQL invoked.
     *
     * @since [*next-version*]
     */
    public function testMigrateUpSpecific()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance(['_getMigrationFilePatterns'], $database);
        $reflect = $this->reflect($subject);

        $mTable = 'test_table';
        $mTable1Col1 = 'id';
        $mTable1Col2 = 'name';
        $mTable2 = 'test_table2';
        $mTable2Col1 = 'id';
        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'up' => [
                        '1.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s int)', $mTable, $mTable1Col1),
                        '2.sql' => sprintf('ALTER TABLE %1$s ADD %2$s int', $mTable, $mTable1Col2),
                        '3.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (%2$s int)', $mTable2, $mTable2Col1),
                    ],
                ],
            ]
        );

        $subject->method('_getMigrationFilePatterns')
                ->willReturn(
                    [
                        $fileSystem->url().'/sql/up/' => '/^%d\.sql$/',
                    ]
                );

        $expected = [
            static::VERSION_COL => 2,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();
        $reflect->_up(2);

        $tables = $this->getConnection()->getMetaData()->getTableNames();
        $columns1 = $this->getConnection()->getMetaData()->getTableColumns($mTable);
        $columns2 = $this->getConnection()->getMetaData()->getTableColumns($mTable2);

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Incorrect database migration version.');

        $this->assertContains($mTable, $tables, 'Table added by migration 1 not found in database.');
        $this->assertEquals(
            [$mTable1Col1, $mTable1Col2],
            $columns1,
            'Incorrect columns for table created in migration #1 and modified in migration #2.'
        );

        $this->assertNotContains($mTable2, $tables, 'Table added by migration 3 was found but should not exist.');
    }

    /**
     * Tests the migration up procedure to assert whether the version is correctly incremented and the SQL invoked.
     *
     * @since [*next-version*]
     */
    public function testMigrateDown()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance(['_getMigrationFilePatterns'], $database);
        $reflect = $this->reflect($subject);

        $mTable1 = 'test_table';
        $mTable2 = 'test_table2';
        $mTable3 = 'test_table3';

        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'up' => [
                        '1.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable1),
                        '2.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable2),
                        '3.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable3),
                    ],
                    'down' => [
                        '1.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable1),
                        '2.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable2),
                        '3.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable3),
                    ],
                ],
            ]
        );

        $subject->method('_getMigrationFilePatterns')
                ->willReturnCallback(
                    function ($direction) use ($fileSystem) {
                        return [
                            sprintf('%1$s/sql/%2$s/', $fileSystem->url(), $direction) => '/^%d\.sql$/',
                        ];
                    }
                );

        $expected = [
            static::VERSION_COL => 0,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();
        $reflect->_up();
        $reflect->_down();

        $tables = $this->getConnection()->getMetaData()->getTableNames();

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Incorrect database migration version.');

        $this->assertNotContains(
            $mTable1,
            $tables,
            'Table added by migration 1 found in database but should not exist.'
        );
        $this->assertNotContains(
            $mTable2,
            $tables,
            'Table added by migration 2 found in database but should not exist.'
        );
        $this->assertNotContains(
            $mTable3,
            $tables,
            'Table added by migration 3 found in database but should not exist.'
        );
    }

    /**
     * Tests the migration up procedure to assert whether the version is correctly incremented and the SQL invoked.
     *
     * @since [*next-version*]
     */
    public function testMigrateDownSpecific()
    {
        $driver = new PdoSqliteDriverStub($this->_getPdo(), static::DB_NAME);
        $database = $this->createDatabase($driver);
        $subject = $this->createInstance(['_getMigrationFilePatterns'], $database);
        $reflect = $this->reflect($subject);

        $mTable1 = 'test_table';
        $mTable2 = 'test_table2';
        $mTable3 = 'test_table3';

        $fileSystem = $this->createFileSystem(
            [
                'sql' => [
                    'up' => [
                        '1.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable1),
                        '2.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable2),
                        '3.sql' => sprintf('CREATE TABLE IF NOT EXISTS %1$s (id int)', $mTable3),
                    ],
                    'down' => [
                        '1.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable1),
                        '2.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable2),
                        '3.sql' => sprintf('DROP TABLE IF EXISTS %1$s', $mTable3),
                    ],
                ],
            ]
        );

        $subject->method('_getMigrationFilePatterns')
                ->willReturnCallback(
                    function ($direction) use ($fileSystem) {
                        return [
                            sprintf('%1$s/sql/%2$s/', $fileSystem->url(), $direction) => '/^%d\.sql$/',
                        ];
                    }
                );

        $expected = [
            static::VERSION_COL => 2,
            static::STATUS_COL => AbstractDatabase::STATUS_COMPLETE,
        ];

        $reflect->_reset();
        $reflect->_up();
        $reflect->_down(2);

        $tables = $this->getConnection()->getMetaData()->getTableNames();

        $this->assertEquals($expected, $reflect->getCurrentVersion(), 'Incorrect database migration version.');

        $this->assertContains(
            $mTable1,
            $tables,
            'Table added by migration 1 not found in database.'
        );
        $this->assertContains(
            $mTable2,
            $tables,
            'Table added by migration 2 not found in database.'
        );
        $this->assertNotContains(
            $mTable3,
            $tables,
            'Table added by migration 3 found in database but should not exist.'
        );
    }
}
