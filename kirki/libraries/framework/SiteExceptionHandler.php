<?php

/**
 * Translates thrown exceptions into WordPress-native site route error responses.
 * Maps authorization, not-found, and validation failures to HTTP status pages.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Kirki\Framework\Exceptions\AuthorizationException;
use Kirki\Framework\Exceptions\ModelNotFoundException;
use Kirki\Framework\Exceptions\ValidationException;
use Kirki\Framework\Http\Response;
use Exception;
class SiteExceptionHandler
{
    /**
     * Handle an exception for a site route request.
     *
     * @param Exception $exception The exception to handle.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function handle(Exception $exception)
    {
        if ($exception instanceof AuthorizationException) {
            static::fail(Response::FORBIDDEN, $exception->getMessage() ?: 'Forbidden');
        }
        if ($exception instanceof ModelNotFoundException) {
            static::fail(Response::NOT_FOUND, $exception->getMessage() ?: 'Not Found');
        }
        if ($exception instanceof ValidationException) {
            static::fail(Response::UNPROCESSABLE_ENTITY, $exception->getMessage() ?: 'Validation failed');
        }
        $status = (int) $exception->getCode();
        if ($status < 100 || $status > 599) {
            $status = Response::INTERNAL_SERVER_ERROR;
        }
        if (\function_exists('error_log')) {
            \error_log($exception->getMessage());
        }
        static::fail($status, $exception->getMessage() ?: 'Internal Server Error');
    }
    /**
     * Stop the request with a WordPress error page at the given HTTP status.
     *
     * @param int $status HTTP status code.
     * @param string $message Error message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected static function fail(int $status, string $message)
    {
        status_header($status);
        nocache_headers();
        wp_die(esc_html($message), esc_html($message), ['response' => $status]);
    }
}
