<?php

/**
 * Contract for sending structured JSON responses.
 * Implementations should return a WordPress-compatible REST response.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Response
{
    /**
     * Return a JSON-formatted REST response.
     *
     * @param array $data The response data.
     * @param int $code Optional. HTTP status code. Default Response::OK.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public function json(array $data, int $code = 200);
    /**
     * Return a text-formatted REST response.
     *
     * @param string $data The response data.
     * @param int $code Optional. HTTP status code. Default Response::OK.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public function text(string $data, int $code = 200);
}
