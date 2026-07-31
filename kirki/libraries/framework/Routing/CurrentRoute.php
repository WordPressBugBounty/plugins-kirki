<?php

/**
 * Request-scoped context for the currently dispatching site route.
 * Stores the matched route name and params for Route::is() and related helpers.
 *
 * @package    Framework
 * @subpackage Routing
 * @since      1.0.0
 */
namespace Kirki\Framework\Routing;

\defined('ABSPATH') || exit;
class CurrentRoute
{
    /**
     * Name of the currently dispatching route.
     *
     * @var string|null
     *
     * @since 1.0.0
     */
    protected static $name = null;
    /**
     * Params for the currently dispatching route.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected static $params = [];
    /**
     * Set the currently dispatching site route context.
     *
     * @param string|null $name Route name.
     * @param array $params Route params.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function set($name, array $params = [])
    {
        static::$name = $name;
        static::$params = $params;
    }
    /**
     * Reset the current route context.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function reset()
    {
        static::$name = null;
        static::$params = [];
    }
    /**
     * Whether the currently dispatching route is the one named $name.
     *
     * @param string $name Route name.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function is(string $name)
    {
        return static::$name !== null && static::$name === $name;
    }
    /**
     * Get a single param from the currently dispatching route.
     *
     * @param string $key Param name.
     * @param mixed $default Fallback when missing.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function param(string $key, $default = null)
    {
        return static::$params[$key] ?? $default;
    }
    /**
     * Get all params for the currently dispatching route.
     *
     * @param mixed $default Fallback when no params are available.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function params($default = [])
    {
        return !empty(static::$params) ? static::$params : $default;
    }
}
