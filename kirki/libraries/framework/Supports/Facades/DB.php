<?php

/**
 * Static proxy for the database manager resolved from the application container.
 * Exposes query builder, transaction, and raw expression helpers through a concise facade API.
 * Forwards static calls to the underlying DatabaseManager service binding.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
/**
 * Static proxy for the database manager resolved from the application container.
 *
 * @method static void begin_transaction()
 * @method static void commit()
 * @method static void rollback()
 * @method static \Framework\Database\Query\QueryBuilder table(string $table, string|null $as = null)
 * @method static \Framework\Database\Query\Expression raw($value)
 * @method static void enable_query_log()
 * @method static void disable_query_log()
 * @method static void flush_query_log()
 * @method static array get_query_log()
 * @see    \Framework\Database\Connection\DatabaseManager
 */
class DB extends Facade
{
    /**
     * Get the accessor for the DB facade
     *
     * @return string The accessor name
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'db';
    }
}
