<?php

/**
 * Exception thrown when a date or timezone value cannot be parsed.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use RuntimeException;
use Throwable;
class InvalidDateFormatException extends RuntimeException
{
    /**
     * Create a new InvalidDateFormatException instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param \Throwable|null $previous The previous exception.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
