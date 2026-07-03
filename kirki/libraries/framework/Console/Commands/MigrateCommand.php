<?php

/**
 * Runs all registered migrations that have not yet been applied.
 * Delegates to the Migrator service and reports success via WP-CLI.
 * The primary entry point for applying schema changes in a WordPress plugin context.
 *
 * @package    Framework
 * @subpackage Console\Commands
 * @since      1.0.0
 */
namespace Kirki\Framework\Console\Commands;

\defined('ABSPATH') || exit;
use Kirki\Framework\Console\CommandBase;
use function Kirki\Framework\migrator;
class MigrateCommand extends CommandBase
{
    /**
     * Run the command
     *
     * @param mixed $args The positional arguments.
     * @param mixed $assoc The associative arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run($args, $assoc)
    {
        migrator()->run();
        \WP_CLI::success('Migrations run successfully.');
    }
}
