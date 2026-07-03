<?php

/**
 * Contract for interacting with an HTTP request in a normalized way.
 * This interface abstracts key methods for accessing request data, headers, routes, and HTTP method.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Request
{
    /**
     * Get the HTTP method of the request (GET, POST, etc).
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_method();
    /**
     * Get the requested route or endpoint.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_route();
    /**
     * Get all HTTP headers from the request.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_headers();
    /**
     * Get a single HTTP header from the request.
     *
     * @param string $name The name.
     * @param mixed $default The default.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_header(string $name, $default = null);
    /**
     * Get all request input attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function all();
    /**
     * Get the validated data from the request.
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function validated();
    /**
     * Get the sanitized data from the request.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function sanitized();
    /**
     * Get request data excluding specified attributes.
     *
     * @param array $attributes Keys to exclude.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function except(array $attributes);
    /**
     * Get a single attribute by key.
     *
     * @param string $key The key to retrieve.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function only(string $key);
    /**
     * Alias of `only()`. Get input value by key.
     *
     * @param string $key The key to retrieve.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function input(string $key);
    /**
     * Get a value from the request with optional default and type casting.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default Default value if the key doesn't exist.
     * @param string|null $type Optional type to cast the result to: 
     * int, float, bool, string, array with proper sanitization.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get(string $key, $default = null, $type = null);
    /**
     * Check if the request has the provided key
     *
     * @param string $key The key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function has(string $key);
    /**
     * Get a string value.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_string(string $key, $default = null);
    /**
     * Get a safe input value with whitelist.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default Default value if the key doesn't exist.
     * @param array $whitelist Array of allowed values.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_whitelisted(string $key, $default = null, array $whitelist = []);
    /**
     * Get a date value.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_date(string $key, $default = null);
    /**
     * Get a datetime value.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_datetime(string $key, $default = null);
    /**
     * Get a text with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_text(string $key, $default = null);
    /**
     * Get a html supported content with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_html(string $key, $default = null);
    /**
     * Get a email with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_email(string $key, $default = null);
    /**
     * Get a url with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_url(string $key, $default = null);
    /**
     * Get a key value with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_key(string $key, $default = null);
    /**
     * Get a title value with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_title(string $key, $default = null);
    /**
     * Get a file name with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_file_name(string $key, $default = null);
    /**
     * Get mime type with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_mime_type(string $key, $default = null);
    /**
     * Get an integer value.
     *
     * @param string $key The key to retrieve.
     * @param int|null $default Default value if the key doesn't exist.
     *
     * @return int|null
     *
     * @since 1.0.0
     */
    public function get_int(string $key, $default = null);
    /**
     * Get a boolean value.
     *
     * @param string $key The key to retrieve.
     * @param bool $default Default value if the key doesn't exist.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function get_bool(string $key, bool $default = \false);
    /**
     * Get a float value.
     *
     * @param string $key The key to retrieve.
     * @param float|null $default Default value if the key doesn't exist.
     *
     * @return float|null
     *
     * @since 1.0.0
     */
    public function get_float(string $key, $default = null);
    /**
     * Get an array value.
     *
     * @param string $key The key to retrieve.
     * @param array|null $default Default value if the key doesn't exist.
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function get_array(string $key, $default = null);
}
