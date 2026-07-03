<?php

/**
 * Exception thrown when a authorization fails.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Http\Response;
use RuntimeException;
use function Kirki\Framework\message;
class AuthorizationException extends RuntimeException
{
    /**
     * Create a new instance.
     *
     * @param mixed $message The message.
     * @param mixed $code The code.
     * @param mixed $previous The previous.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if ($message === '') {
            $message = message('auth.logged_in_required');
        }
        if ($code === 0) {
            $code = Response::UNAUTHORIZED;
        }
        parent::__construct($message, $code, $previous);
    }
}
