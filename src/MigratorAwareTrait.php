<?php

namespace RebelCode\Migrations;

use Dhii\Util\String\StringableInterface as Stringable;
use Exception as RootException;
use InvalidArgumentException;

/**
 * Common functionality for objects that are aware of a migrator.
 *
 * @since [*next-version*]
 */
trait MigratorAwareTrait
{
    /**
     * The migrator instance.
     *
     * @since [*next-version*]
     *
     * @var MigratorInterface|null
     */
    protected $migrator;

    /**
     * Retrieves the migrator associated with this instance.
     *
     * @since [*next-version*]
     *
     * @return MigratorInterface|null The migrator, if any.
     */
    protected function _getMigrator()
    {
        return $this->migrator;
    }

    /**
     * Sets the migrator for this instance.
     *
     * @since [*next-version*]
     *
     * @param MigratorInterface|null $migrator The migrator, or null.
     */
    protected function _setMigrator($migrator)
    {
        if ($migrator !== null && !($migrator instanceof MigratorInterface)) {
            throw $this->_createInvalidArgumentException(
                $this->__('Argument is not a valid migrator'),
                null,
                null,
                $migrator
            );
        }

        $this->migrator = $migrator;
    }

    /**
     * Creates a new invalid argument exception.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable|null $message  The error message, if any.
     * @param int|null               $code     The error code, if any.
     * @param RootException|null     $previous The inner exception for chaining, if any.
     * @param mixed|null             $argument The invalid argument, if any.
     *
     * @return InvalidArgumentException The new exception.
     */
    abstract protected function _createInvalidArgumentException(
        $message = null,
        $code = null,
        RootException $previous = null,
        $argument = null
    );

    /**
     * Translates a string, and replaces placeholders.
     *
     * @since [*next-version*]
     * @see   sprintf()
     *
     * @param string $string  The format string to translate.
     * @param array  $args    Placeholder values to replace in the string.
     * @param mixed  $context The context for translation.
     *
     * @return string The translated string.
     */
    abstract protected function __($string, $args = [], $context = null);
}
