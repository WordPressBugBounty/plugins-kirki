<?php

/**
 * Executes database seeders, optionally targeting a specific class or the default DatabaseSeeder.
 * Wraps seeding in transaction and error handling with logging on failure.
 * Supports fresh database population after migrations.
 *
 * @package    Framework
 * @subpackage Console\Commands
 * @since      1.0.0
 */
namespace Kirki\Framework\Console\Commands;

\defined('ABSPATH') || exit;
use Kirki\Framework\Console\CommandBase;
use Kirki\Framework\Console\Synopsis;
use Kirki\Framework\Database\Seeder;
use Kirki\Framework\Database\Seeders\DatabaseSeeder;
use Kirki\Framework\Supports\Facades\DB;
use Kirki\Framework\Supports\Facades\Log;
use Kirki\Framework\Supports\Facades\Schema;
use Kirki\Framework\Supports\Str;
use Exception;
use Throwable;
use function Kirki\Framework\app;
use function Kirki\Framework\database_path;
class SeedCommand extends CommandBase
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
        $this->seed();
    }
    /**
     * Discover the seeders
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function discover()
    {
        $seeders = [];
        $seeders_files = \glob(database_path('seeders/*.php'));
        if (empty($seeders_files)) {
            return $seeders;
        }
        foreach ($seeders_files as $file) {
            $classname = $this->classname($this->filename($file));
            if ($this->exists($classname)) {
                $seeders[] = $classname;
            }
        }
        return $seeders;
    }
    /**
     * Get the seeders
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function seeders()
    {
        if (!empty($this->assoc['class'])) {
            return $this->seeder_classes($this->assoc['class']);
        }
        return $this->discover();
    }
    /**
     * Get the seeder classes
     *
     * @param mixed $class The class.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function seeder_classes($class)
    {
        $classes = Str::split(',', $class);
        foreach ($classes as $class) {
            $classname = $this->classname($class);
            if ($this->exists($classname)) {
                $seeders[] = $classname;
            }
        }
        return $seeders;
    }
    /**
     * Get the filename of the seeder
     *
     * @param mixed $path The path.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function filename($path)
    {
        return \basename($path, '.php');
    }
    /**
     * Get the classname of the seeder
     *
     * @param mixed $filename The filename.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function classname($filename)
    {
        $namespace = app()->get_seeders_namespace() . '\\';
        return $namespace . Str::pascal($filename);
    }
    /**
     * Check if the seeder exists
     *
     * @param mixed $class The class.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function exists($class)
    {
        return \class_exists($class);
    }
    /**
     * Seed the database
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function seed()
    {
        $seeders = $this->seeders();
        DB::begin_transaction();
        try {
            Schema::disabled_checking_foreign_key_constraints();
            if (!\class_exists(DatabaseSeeder::class)) {
                $instance = app()->make(Seeder::class);
                $instance->call($seeders);
                $instance();
            } else {
                $instance = app()->make(DatabaseSeeder::class);
                $instance->run();
                $instance();
            }
        } catch (Exception $exception) {
            DB::rollback();
            Log::error($exception->getMessage());
        } finally {
            Schema::enabled_checking_foreign_key_constraints();
        }
        DB::commit();
        \WP_CLI::success('Seeder run successfully');
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
        $this->summary('Seed the database')->description("## EXAMPLES \n\n wp kirki db:seed")->synopsis(Synopsis::type('assoc')->name('class')->description('The seeder class name')->optional());
    }
}
