<?php

namespace RebelCode\Migrations\Exception;

use Dhii\Util\String\StringableInterface as Stringable;
use Exception as RootException;
use RebelCode\Migrations\MigratorInterface;

/**
 * Concrete implementation of an exception thrown in relation to a migration.
 *
 * @since [*next-version*]
 */
class MigratorException extends AbstractMigratorException implements MigratorExceptionInterface
{
    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null $message  The error message, if any.
     * @param int|null               $code     The error code, if any.
     * @param RootException|null     $previous The previous exception for chaining, if any.
     * @param MigratorInterface|null $migrator The migrator instance, if any.
     */
    public function __construct($message = null, $code = null, RootException $previous = null, $migrator = null)
    {
        parent::__construct((string) $message, (int) $code, $previous);

        $this->_setMigrator($migrator);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getMigrator()
    {
        return $this->_getMigrator();
    }
}
