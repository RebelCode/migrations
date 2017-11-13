<?php

namespace RebelCode\Migrations\UnitTest;

use ByJG\DbMigration\Database\DatabaseInterface;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Http\Message\UriInterface;
use RebelCode\Migrations\Exception\CouldNotMigrateExceptionInterface;
use RebelCode\Migrations\Exception\MigratorExceptionInterface;
use RebelCode\Migrations\MigratorInterface;
use RebelCode\Migrations\MySqlFormatMigrator;
use Xpmock\TestCase;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class MySqlFormatMigratorTest extends TestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\Migrations\MySqlFormatMigrator';

    /**
     * Creates a new virtual file system.
     *
     * @since [*next-version*]
     *
     * @param array $structure The VFS structure.
     *
     * @return vfsStreamDirectory
     */
    public function createFileSystem(array $structure = [])
    {
        return vfsStream::setup(
            'root',
            null,
            $structure
        );
    }

    /**
     * Creates a new database mock instance.
     *
     * @since [*next-version*]
     *
     * @return DatabaseInterface
     */
    public function createDatabase()
    {
        $builder = $this->getMockBuilder(DatabaseInterface::class)
                        ->setMethods(
                            [
                                'prepareEnvironment',
                                'createDatabase',
                                'dropDatabase',
                                'getVersion',
                                'updateVersionTable',
                                'executeSql',
                                'setVersion',
                                'createVersion',
                            ]
                        );

        return $builder->getMockForAbstractClass();
    }

    /**
     * Creates a new URI mock instance.
     *
     * @since [*next-version*]
     *
     * @return UriInterface
     */
    public function createUri()
    {
        $mock = $this->mock(UriInterface::class)
                     ->getScheme()
                     ->getAuthority()
                     ->getUserInfo()
                     ->getHost()
                     ->getPort()
                     ->getPath()
                     ->getQuery()
                     ->getFragment()
                     ->withScheme()
                     ->withUserInfo()
                     ->withHost()
                     ->withPort()
                     ->withPath()
                     ->withQuery()
                     ->withFragment()
                     ->__toString();

        return $mock->new();
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $uri     = $this->createUri();
        $db      = $this->createDatabase();
        $vfs     = $this->createFileSystem();
        $subject = new MySqlFormatMigrator($uri, $db, $vfs->url());

        $this->assertInstanceOf(
            MigratorInterface::class,
            $subject,
            'A valid instance of the test subject could not be created.'
        );
    }

    /**
     * Tests the SQL preparation function to assert whether formatting works as expected.
     *
     * @since [*next-version*]
     */
    public function testPrepareSql()
    {
        $uri             = $this->createUri();
        $database        = $this->createDatabase();
        $fileSystem      = $this->createFileSystem();
        $placeholder     = uniqid('placeholder-');
        $replacement     = uniqid('replacement-');
        $fullPlaceholder = sprintf('{%s}', $placeholder);

        $formatters = [
            $placeholder => function() use ($replacement) {
                return $replacement;
            },
        ];

        $subject = new MySqlFormatMigrator($uri, $database, $fileSystem->url(), $formatters);
        $reflect = $this->reflect($subject);

        $before = sprintf('SELECT %s FROM some_table', $fullPlaceholder);
        $after  = $reflect->_prepareSql($before);

        $this->assertNotEquals($before, $after, 'Result SQL is the same as the input SQL.');
        $this->assertNotContains($fullPlaceholder, $after, 'Result SQL still contains the placeholder.');
        $this->assertContains($replacement, $after, 'Result SQL does not contain the replacement.');
    }

    /**
     * Tests the migrator exception factory method to assert whether it correctly creates exception instances.
     *
     * @since [*next-version*]
     */
    public function testCreateMigratorException()
    {
        $uri        = $this->createUri();
        $database   = $this->createDatabase();
        $fileSystem = $this->createFileSystem();
        $subject    = new MySqlFormatMigrator($uri, $database, $fileSystem->url());
        $reflect    = $this->reflect($subject);

        $message   = uniqid('message-');
        $code      = rand(0, 10);
        $previous  = new Exception();
        $exception = $reflect->_createMigratorException($message, $code, $previous);

        $this->assertInstanceOf(
            MigratorExceptionInterface::class,
            $exception,
            'Created exception does not implement expected interface.'
        );

        $this->assertEquals($message, $exception->getMessage(), 'Exception message is incorrect.');
        $this->assertEquals($code, $exception->getCode(), 'Exception code is incorrect.');
        $this->assertSame($previous, $exception->getPrevious(), 'Inner exception is incorrect.');
        $this->assertSame($subject, $exception->getMigrator(), 'Exception migration is incorrect.');
    }

    /**
     * Tests the could not migrate exception factory method to assert whether it correctly creates exception instances.
     *
     * @since [*next-version*]
     */
    public function testCreateCouldNotMigrateException()
    {
        $uri        = $this->createUri();
        $database   = $this->createDatabase();
        $fileSystem = $this->createFileSystem();
        $subject    = new MySqlFormatMigrator($uri, $database, $fileSystem->url());
        $reflect    = $this->reflect($subject);

        $message   = uniqid('message-');
        $code      = rand(0, 10);
        $previous  = new Exception();
        $version   = rand();
        $exception = $reflect->_createCouldNotMigrateException($message, $code, $previous, $version);

        $this->assertInstanceOf(
            CouldNotMigrateExceptionInterface::class,
            $exception,
            'Created exception does not implement expected interface.'
        );

        $this->assertEquals($message, $exception->getMessage(), 'Exception message is incorrect.');
        $this->assertEquals($code, $exception->getCode(), 'Exception code is incorrect.');
        $this->assertSame($previous, $exception->getPrevious(), 'Inner exception is incorrect.');
        $this->assertSame($subject, $exception->getMigrator(), 'Exception migration is incorrect.');
        $this->assertEquals($version, $exception->getMigrationVersion(), 'Exception migration version is incorrect.');
    }
}
