<?php

namespace RebelCode\Migrations\Exception\UnitTest;

use Exception;
use RebelCode\Migrations\Exception\MigratorException;
use RebelCode\Migrations\MigratorInterface;
use Xpmock\TestCase;

/**
 * Tests {@see RebelCode\Migrations\Exception\MigratorException}.
 *
 * @since [*next-version*]
 */
class MigratorExceptionTest extends TestCase
{
    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @return MigratorInterface
     */
    public function createMigrator()
    {
        $mock = $this->mock('RebelCode\Migrations\MigratorInterface')
                     ->up()
                     ->down()
                     ->reset();

        return $mock->new();
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = new MigratorException();

        $this->assertInstanceof(
            'RebelCode\Migrations\Exception\MigratorExceptionInterface',
            $subject,
            'Test subject does not implement expected parent.'
        );

        $this->assertInstanceof(
            'Exception',
            $subject,
            'Test subject is not an exception.'
        );
    }

    /**
     * Test the constructor to ensure that all properties are correctly set and retrieved.
     *
     * @since [*next-version*]
     */
    public function testConstructor()
    {
        $message = uniqid('message-');
        $code = rand();
        $previous = new Exception();
        $migrator = $this->createMigrator();
        $subject = new MigratorException($message, $code, $previous, $migrator);

        $this->assertEquals($message, $subject->getMessage(), 'Set and retrieved messages are not the same.');
        $this->assertEquals($code, $subject->getCode(), 'Set and retrieved code are not the same.');
        $this->assertEquals($previous, $subject->getPrevious(), 'Set and retrieved inner exception are not the same.');
        $this->assertEquals($migrator, $subject->getMigrator(), 'Set and retrieved migrator are not the same.');
    }
}
