<?php

/**
 * Facade proxy for SchemaManager database schema operations.
 * Exposes create, drop, and foreign key constraint toggles.
 * Used by migrations to define and modify tables.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
/**
 * Facade proxy for SchemaManager database schema operations.
 *
 * @method static void create(string $table, \Closure $callback)
 * @method static void drop_if_exists(string $table)
 * @method static void drop(string $table)
 * @method static void enabled_checking_foreign_key_constraints()
 * @method static void disabled_checking_foreign_key_constraints()
 * @method static array get_column_listing(string $table)
 * @method static array get_columns(string $table)
 * @see    \Framework\Database\Schema\SchemaManager
 */
class Schema extends Facade
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
        return 'schema';
    }
}
