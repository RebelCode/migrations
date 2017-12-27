<?php

namespace RebelCode\Migrations\Exception\UnitTest;

use Xpmock\TestCase;
use RebelCode\Migrations\Exception\MigratorExceptionInterface as TestSubject;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class MigratorExceptionInterfaceTest extends TestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'RebelCode\Migrations\Exception\MigratorExceptionInterface';

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @return TestSubject
     */
    public function createInstance()
    {
        $mock = $this->mock(static::TEST_SUBJECT_CLASSNAME)
                     ->getMigrator()
                     ->getMessage()
                     ->getCode()
                     ->getPrevious()
                     ->getLine()
                     ->getFile()
                     ->getTrace()
                     ->getTraceAsString()
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
        $subject = $this->createInstance();

        $this->assertInstanceOf(
            static::TEST_SUBJECT_CLASSNAME,
            $subject,
            'A valid instance of the test subject could not be created.'
        );

        $this->assertInstanceOf(
            'RebelCode\Migrations\MigratorAwareInterface',
            $subject,
            'Test subject does not implement expected interface.'
        );

        $this->assertInstanceOf(
            'Dhii\Exception\ThrowableInterface',
            $subject,
            'Test subject does not implement expected interface.'
        );
    }
}
