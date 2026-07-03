<?php

/**
 * Persists applied migration class names in WordPress options and reads registered migrations from container tags.
 * Tracks which migrations have run and supports rollback state queries.
 * Bridges the migrator with WordPress option storage.
 *
 * @package    Framework
 * @subpackage Database\Migrations
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Migrations;

\defined('ABSPATH') || exit;
use Kirki\Framework\Constants\OptionKeys;
use Kirki\Framework\Supports\Facades\Option;
use function Kirki\Framework\app;
class MigrationRepository
{
    /**
     * Get the previous migrations.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_previous_migrations()
    {
        return Option::get(OptionKeys::MIGRATIONS, []);
    }
    /**
     * Update the migrations.
     *
     * @param array $migrations The migrations.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function update_migrations(array $migrations)
    {
        Option::set(OptionKeys::MIGRATIONS, $migrations);
    }
    /**
     * Remove the migrations.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function remove_migrations()
    {
        Option::delete(OptionKeys::MIGRATIONS);
    }
    /**
     * Get the registered migrations.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_registered_migrations()
    {
        return app()->tagged('app.migrations');
    }
    /**
     * Check if the rollback is enabled.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_rollback_enabled()
    {
        return \true;
        // @todo: will be handled later
    }
}
