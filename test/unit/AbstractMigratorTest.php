<?php

namespace RebelCode\Migrations\UnitTest;

use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use Xpmock\TestCase;
use RebelCode\Migrations\AbstractMigrator as TestSubject;

/**
 * Tests {@see TestSubject}.
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

        return $mock;
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
        $increment   = -1;

        $this->assertTrue($reflect->canContinue($currVersion, $upVersion, $increment));
    }
}
