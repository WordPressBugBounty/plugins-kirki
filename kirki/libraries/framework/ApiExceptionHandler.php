<?php

/**
 * Translates thrown exceptions into consistent JSON REST responses for the API layer.
 * Maps ValidationException and ModelNotFoundException to appropriate HTTP status codes with structured error payloads.
 * Falls back to a generic JSON error using the exception code when no specialized handler applies.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Kirki\Framework\Exceptions\ModelNotFoundException;
use Kirki\Framework\Exceptions\ValidationException;
use Kirki\Framework\Http\Response;
use Exception;
use function Kirki\Framework\response;
class ApiExceptionHandler
{
    /**
     * Get the response for the exception.
     *
     * @param Exception $exception The exception to get the response for.
     *
     * @return Response The response for the exception.
     *
     * @since 1.0.0
     */
    public static function get_response(Exception $exception)
    {
        if ($exception instanceof ValidationException) {
            $response = ['message' => $exception->getMessage(), 'errors' => $exception->get_errors()];
            return response()->json($response, Response::UNPROCESSABLE_ENTITY);
        }
        if ($exception instanceof ModelNotFoundException) {
            $response = ['message' => $exception->getMessage()];
            return response()->json($response, Response::NOT_FOUND);
        }
        return static::fallback_response($exception);
    }
    /**
     * Get the fallback response for the exception.
     *
     * @param Exception $exception The exception to get the fallback response for.
     *
     * @return Response The fallback response for the exception.
     *
     * @since 1.0.0
     */
    protected static function fallback_response(Exception $exception)
    {
        $status_code = (int) $exception->getCode();
        if ($status_code < 100 || $status_code > 599) {
            $status_code = Response::INTERNAL_SERVER_ERROR;
            // fallback to 500
        }
        $response = ['message' => $exception->getMessage()];
        return response()->json($response, $status_code);
    }
}
