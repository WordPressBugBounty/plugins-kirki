<?php

/**
 * Coordinates schema operations such as creating, altering, and dropping database tables.
 * Bridges migration workflows with the structure builder and SQL compiler services.
 * Exposes the primary entry point for programmatic table management in plugins.
 *
 * @package    Framework
 * @subpackage Database\Schema
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Schema;

\defined('ABSPATH') || exit;
use Closure;
use Kirki\Framework\Database\Connection\Connection;
use Exception;
class SchemaManager
{
    /**
     * The connection.
     *
     * @var Connection|null
     *
     * @since 1.0.0
     */
    protected $connection = null;
    /**
     * SchemaManager constructor.
     *
     * @param Connection $connection The database connection instance.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Create a new table in the database.
     *
     * @param string $table The name of the table to create.
     * @param Closure $callback The callback to define the table structure.
     *
     * @return void
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function create($table, Closure $callback)
    {
        $structure = new Structure($table, $this->connection);
        $callback($structure);
        $create_sql = $structure->get_table_structure();
        $this->connection->get_db()->query($create_sql);
        if (!empty($this->connection->get_db()->last_error)) {
            throw new Exception($this->connection->get_db()->last_error);
        }
    }
    /**
     * Disable foreign key constraint checking.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function disabled_checking_foreign_key_constraints()
    {
        $this->connection->get_db()->query("SET FOREIGN_KEY_CHECKS = 0");
    }
    /**
     * Enable foreign key constraint checking.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function enabled_checking_foreign_key_constraints()
    {
        $this->connection->get_db()->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    /**
     * Drop a table if it exists.
     *
     * @param string $table The name of the table to drop.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function drop_if_exists(string $table)
    {
        $this->disabled_checking_foreign_key_constraints();
        $this->connection->get_db()->query(\sprintf('DROP TABLE IF EXISTS %s', $this->connection->get_query_compiler()->wrap_table($table)));
        $this->enabled_checking_foreign_key_constraints();
    }
    /**
     * Drop a table.
     *
     * @param string $table The name of the table to drop.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function drop(string $table)
    {
        $this->disabled_checking_foreign_key_constraints();
        $this->connection->get_db()->query(\sprintf('DROP TABLE %s', $this->connection->get_query_compiler()->wrap_table($table)));
        $this->enabled_checking_foreign_key_constraints();
    }
    /**
     * Get the column list for a table.
     *
     * @param string $table The name of the table.
     *
     * @return array
     * 
     * @throws Exception
     *
     * @since 1.0.0
     */
    public function get_column_listing($table)
    {
        return \array_column($this->get_columns($table), 'name');
    }
    /**
     * Get the columns for a table.
     *
     * @param string $table The name of the table.
     *
     * @return array
     *
     * @throws Exception
     * 
     * @since 1.0.0
     */
    public function get_columns($table)
    {
        $table = $this->connection->get_table_prefix() . $table;
        return $this->connection->get_db()->get_results($this->connection->get_schema_compiler()->compile_database_columns($table), ARRAY_A);
    }
}
