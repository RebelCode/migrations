<?php

namespace RebelCode\Migrations\FuncTest;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_MockObject_MockObject;
use Xpmock\TestCase;

/**
 * Tests {@see RebelCode\Migrations\AbstractMigrator}.
 *
 * @since [*next-version*]
 */
class AbstractMigratorTest extends TestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\Migrations\AbstractMigrator';

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @param array $mockMethods Additional methods to mock.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function createInstance(array $mockMethods = [])
    {
        $mock = $this->getMockBuilder(static::TEST_SUBJECT_CLASSNAME)
                     ->setMethods(
                         array_merge(
                             $mockMethods,
                             [
                                 '__',
                                 '_createCouldNotMigrateException',
                                 '_normalizeInt',
                                 '_normalizeString',
                                 '_prepareSql',
                                 '_getMigrationFilePattern',
                             ]
                         )
                     )
                     ->disableOriginalConstructor()
                     ->getMockForAbstractClass();

        $mock->method('__')->willReturnArgument(0);
        $mock->method('_prepareSql')->willReturnArgument(0);
        $mock->method('_normalizeInt')->willReturnArgument(0);
        $mock->method('_normalizeString')->willReturnArgument(0);

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 5);
        $upVersion   = rand(6, 10);
        $increment   = 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(6, 10);
        $upVersion   = rand(1, 5);
        $increment   = 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion   = $currVersion;
        $increment   = 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion   = null;
        $increment   = 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(6, 10);
        $upVersion   = rand(1, 5);
        $increment   = - 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 5);
        $upVersion   = rand(6, 10);
        $increment   = - 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion   = $currVersion;
        $increment   = - 1;

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
        $subject     = $this->createInstance();
        $reflect     = $this->reflect($subject);
        $currVersion = rand(1, 10);
        $upVersion   = null;
        $increment   = - 1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }

    /**
     * Tests the matching files method to ensure that it correctly matches files in a directory with a regex pattern.
     *
     * @since [*next-version*]
     */
    public function testGetMatchingFiles()
    {
        $subject    = $this->createInstance();
        $reflect    = $this->reflect($subject);
        $pattern    = '/foo[0-9]/'; // match all "foo" with a number
        $fileSystem = $this->createFileSystem(
            [
                $f1 = uniqid('foo-')  => '',
                $f2 = uniqid('foo1-') => '',
                $f3 = uniqid('foo8-') => '',
                $f4 = uniqid('bar-')  => '',
                $f5 = uniqid('baz-')  => '',
            ]
        );

        $files = $reflect->_getMatchingFiles($fileSystem->url(), $pattern);

        $this->assertCount(2, $files, 'Expected number of matched files is incorrect.');
        $this->assertContains($f2, $files, 'Match files do not contain expected file.');
        $this->assertContains($f3, $files, 'Match files do not contain expected file.');
    }

    /**
     * Tests the migration files getter method with a direction of "up".
     *
     * @since [*next-version*]
     */
    public function testGetMigrationFilesUp()
    {
        $subject    = $this->createInstance();
        $reflect    = $this->reflect($subject);
        $version    = 2;
        $increment  = 1;
        $direction  = 'up';
        $structure  = [
            'migrations' => [
                'up-1.sql'   => uniqid('up-1-'),
                'up-2.sql'   => uniqid('up-2-'),
                'up-3.sql'   => uniqid('up-3-'),
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
                        $fileSystem->url() . '/migrations' => '/^up-%d\.sql$/',
                    ]
                );

        $files = $reflect->_getMigrationFiles($version, $increment);

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
        $subject    = $this->createInstance();
        $reflect    = $this->reflect($subject);
        $version    = 3;
        $increment  = - 1;
        $direction  = 'down';
        $structure  = [
            'migrations' => [
                'up-1.sql'   => uniqid('up-1-'),
                'up-2.sql'   => uniqid('up-2-'),
                'up-3.sql'   => uniqid('up-3-'),
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
                        $fileSystem->url() . '/migrations' => '/^down-%d\.sql$/',
                    ]
                );

        $files = $reflect->_getMigrationFiles($version, $increment);

        $this->assertCount(1, $files, 'Resulting file list contains more than one entry.');
        $this->assertContains('down-3.sql', $files, 'The expected file is not in the resulting file list.');
    }
}
