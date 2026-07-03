<?php

/**
 * Handles standardized REST API responses for the plugin.
 *
 * @package    Framework
 * @subpackage Http
 * @since      1.0.0
 */
namespace Kirki\Framework\Http;

\defined('ABSPATH') || exit;
class Response
{
    /**
     * HTTP status code for a successful request.
     *
     * @since 1.0.0
     * @var   int
     */
    public const OK = 200;
    /**
     * HTTP status code for a successfully created resource.
     *
     * @since 1.0.0
     * @var   int
     */
    public const CREATED = 201;
    /**
     * HTTP status code for a successful request with no content.
     *
     * @since 1.0.0
     * @var   int
     */
    public const NO_CONTENT = 204;
    /**
     * HTTP status code for a multi-status response.
     *
     * @since 1.0.0
     * @var   int
     */
    public const MULTI_STATUS = 207;
    /**
     * HTTP status code for a bad request.
     *
     * @since 1.0.0
     * @var   int
     */
    public const BAD_REQUEST = 400;
    /**
     * HTTP status code for an unauthorized request.
     *
     * @since 1.0.0
     * @var   int
     */
    public const UNAUTHORIZED = 401;
    /**
     * HTTP status code when authentication is required and has failed or has not yet been provided.
     *
     * @since 1.0.0
     * @var   int
     */
    public const FORBIDDEN = 403;
    /**
     * HTTP status code when a requested resource is not found.
     *
     * @since 1.0.0
     * @var   int
     */
    public const NOT_FOUND = 404;
    /**
     * HTTP status code when a request method is not allowed.
     *
     * @since 1.0.0
     * @var   int
     */
    public const METHOD_NOT_ALLOWED = 405;
    /**
     * HTTP status code when a conflict occurs.
     *
     * @since 1.0.0
     * @var   int
     */
    public const CONFLICT = 409;
    /**
     * HTTP status code when a request is unprocessable.
     *
     * @since 1.0.0
     * @var   int
     */
    public const UNPROCESSABLE_ENTITY = 422;
    /**
     * HTTP status code for too many requests (rate limiting).
     *
     * @since 1.0.0
     * @var   int
     */
    public const TOO_MANY_REQUESTS = 429;
    /**
     * HTTP status code for internal server error.
     *
     * @since 1.0.0
     * @var   int
     */
    public const INTERNAL_SERVER_ERROR = 500;
    /**
     * HTTP status code for not implemented.
     *
     * @since 1.0.0
     * @var   int
     */
    public const NOT_IMPLEMENTED = 501;
    /**
     * HTTP status code for service unavailable.
     *
     * @since 1.0.0
     * @var   int
     */
    public const SERVICE_UNAVAILABLE = 503;
    /**
     * The request headers
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $headers = [];
    /**
     * Send a JSON response.
     *
     * @param array $data The data to send.
     * @param int $status The HTTP status code.
     * @param array $headers The headers to send.
     * @param int $options The options to use when encoding the data.
     *
     * @return JsonResponse
     *
     * @since 1.0.0
     */
    public function json($data = [], $status = 200, array $headers = [], int $options = 0)
    {
        return new JsonResponse($data, $status, \array_merge($this->headers, $headers), $options);
    }
    /**
     * Set the headers for the response.
     *
     * @param array $headers The headers to set.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function with_headers(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
}
