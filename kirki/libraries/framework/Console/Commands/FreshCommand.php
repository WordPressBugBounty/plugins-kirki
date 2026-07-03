<?php

/**
 * Drops all database tables and re-runs every pending migration from scratch.
 * Combines schema reset with a full migrate pass for a clean development database.
 * Intended for local or staging resets rather than production use.
 *
 * @package    Framework
 * @subpackage Console\Commands
 * @since      1.0.0
 */
namespace Kirki\Framework\Console\Commands;

\defined('ABSPATH') || exit;
use Kirki\Framework\Console\CommandBase;
use Kirki\Framework\Console\Synopsis;
use function Kirki\Framework\migrator;
class FreshCommand extends CommandBase
{
    /**
     * The sequential arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args;
    /**
     * The assoc arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $assoc;
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
        $this->args = $args;
        $this->assoc = $assoc;
        $this->fresh();
        $this->run_migrations();
        if ($this->need_seeding()) {
            $this->run_seeder();
        }
    }
    /**
     * Fresh the database
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function fresh()
    {
        migrator()->fresh();
    }
    /**
     * Run the migrations
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function run_migrations()
    {
        $command = new MigrateCommand();
        $command->run([], []);
    }
    /**
     * Run the seeder
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function run_seeder()
    {
        $command = new SeedCommand();
        $args = [];
        $assoc = ['class' => $this->assoc['class'] ?? null];
        $command->run($args, $assoc);
    }
    /**
     * Check if the database needs to be seeded
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function need_seeding()
    {
        return !empty($this->assoc['seed']);
    }
    /**
     * Prepare the command synopsis and metadata
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare()
    {
        $this->summary('Drop all the tables and re-run all the migrations')->description("## EXAMPLES \n\n wp kirki db:fresh")->synopsis(Synopsis::type('assoc')->name('class')->description('The seeder class name')->optional())->synopsis(Synopsis::type('flag')->name('seed')->description('Seed the database')->optional());
    }
}
