<?php

/**
 * Trait exposing a class constants as keys, values, or the full map via reflection.
 * Avoids hard-coding constant lists in consuming code.
 * Used by constant holder classes that need runtime enumeration of their defined values.
 *
 * @package    Framework
 * @subpackage Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Concerns;

\defined('ABSPATH') || exit;
trait HasConstants
{
    /**
     * Get the constants.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_constants() : array
    {
        return (new \ReflectionClass(static::class))->getConstants();
    }
    /**
     * Get the constant values.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_constant_values() : array
    {
        return \array_values(static::get_constants());
    }
    /**
     * Get the constant keys.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_constant_keys() : array
    {
        return \array_keys(static::get_constants());
    }
    /**
     * Join the constant values.
     *
     * @param string $separator The separator.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function join(string $separator = ',')
    {
        return \implode($separator, static::get_constant_values());
    }
}
