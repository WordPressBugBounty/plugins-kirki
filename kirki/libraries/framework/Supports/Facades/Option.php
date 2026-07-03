<?php

/**
 * Facade proxy for OptionManager WordPress option access.
 * Exposes get, set, and delete with automatic application prefixing.
 * Used throughout migrations and settings code.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
/**
 * Facade proxy for OptionManager WordPress option access.
 *
 * @method static bool set(string $name, $value, $autoload = null, $with_prefix = true)
 * @method static mixed get(string|array $name, $default = null, $with_prefix = true)
 * @method static bool delete(string $name, $with_prefix = true)
 * @see    \Framework\Managers\OptionManager
 */
class Option extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'option';
    }
}
