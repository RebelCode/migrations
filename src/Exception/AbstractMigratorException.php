<?php

namespace RebelCode\Migrations\Exception;

use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Exception as RootException;
use RebelCode\Migrations\MigratorAwareTrait;

/**
 * Abstract functionality for exceptions thrown in relation to a migrator.
 *
 * @since [*next-version*]
 */
abstract class AbstractMigratorException extends RootException implements MigratorExceptionInterface
{
    /*
     * Provides migrator storage functionality.
     *
     * @since [*next-version*]
     */
    use MigratorAwareTrait;

    /*
     * Provides capability for creating invalid argument exceptions.
     *
     * @since [*next-version*]
     */
    use CreateInvalidArgumentExceptionCapableTrait;

    /*
     * Provides string translating functionality.
     *
     * @since [*next-version*]
     */
    use StringTranslatingTrait;
}
