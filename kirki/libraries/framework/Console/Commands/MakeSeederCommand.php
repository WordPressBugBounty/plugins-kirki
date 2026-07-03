<?php

/**
 * Creates a database seeder class in the seeders directory from a stub template.
 * Names the class and file according to the supplied seeder name.
 * Works with SeedCommand to populate tables during development.
 *
 * @package    Framework
 * @subpackage Console\Commands
 * @since      1.0.0
 */
namespace Kirki\Framework\Console\Commands;

\defined('ABSPATH') || exit;
use Kirki\Framework\Console\CommandBase;
use Kirki\Framework\Console\Synopsis;
use Kirki\Framework\Supports\Facades\File;
use Kirki\Framework\Supports\Str;
use function Kirki\Framework\app;
use function Kirki\Framework\database_path;
class MakeSeederCommand extends CommandBase
{
    /**
     * The arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args;
    /**
     * The arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $assoc;
    /**
     * The base path for the models
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $output_dir;
    /**
     * Initialize the command
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();
        $this->output_dir = database_path('seeders');
    }
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
        if (File::missing($this->output_dir)) {
            File::make_dir($this->output_dir);
        }
        if ($this->is_database_seeder_missing()) {
            $this->create_database_seeder();
        }
        $this->create();
    }
    /**
     * Determine whether the database seeder missing.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_database_seeder_missing()
    {
        return File::missing(database_path('seeders/DatabaseSeeder.php'));
    }
    /**
     * Create database seeder.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function create_database_seeder()
    {
        $data = ['seeder' => 'DatabaseSeeder', 'namespace' => app()->get_seeders_namespace(), 'output_file' => \sprintf('%s/%s.php', $this->output_dir, 'DatabaseSeeder'), 'stub' => $this->get_stub()];
        $output_file = $data['output_file'];
        $namespace = $data['namespace'];
        $seeder = $data['seeder'];
        $content = $this->populate_stub($data);
        if (File::exists($output_file)) {
            \WP_CLI::error('Seeder file already exists.');
        }
        File::put($output_file, $content);
        \WP_CLI::success(\sprintf('Seeder [%s] created successfully.', $namespace . '\\' . $seeder));
    }
    /**
     * Get data for seeder file
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function data()
    {
        return ['seeder' => Str::pascal($this->args[0]), 'namespace' => app()->get_seeders_namespace(), 'output_file' => \sprintf('%s/%s.php', $this->output_dir, Str::pascal($this->args[0])), 'stub' => $this->get_stub()];
    }
    /**
     * Check if the command passed the validation
     *
     * @param mixed $args The positional arguments.
     * @param mixed $assoc The associative arguments.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function passed($args, $assoc)
    {
        return !empty($args[0]);
    }
    /**
     * Create a new model file
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function create()
    {
        $data = $this->data();
        $output_file = $data['output_file'];
        $namespace = $data['namespace'];
        $seeder = $data['seeder'];
        $content = $this->populate_stub($data);
        if (File::exists($output_file)) {
            \WP_CLI::error('Seeder file already exists.');
        }
        File::put($output_file, $content);
        \WP_CLI::success(\sprintf('Seeder [%s] created successfully.', $namespace . '\\' . $seeder));
    }
    /**
     * Get the stub content
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function get_stub()
    {
        $stub_path = $this->stub_path() . '/seeder.stub';
        if (File::missing($stub_path)) {
            \WP_CLI::error('Seeder stub not found: ' . $stub_path);
        }
        return File::get($stub_path);
    }
    /**
     * Populate the stub content
     *
     * @param mixed $data The data payload.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function populate_stub($data)
    {
        return Str::replace(['{{class_name}}', '{{namespace}}'], [$data['seeder'], $data['namespace']], $data['stub']);
    }
    /**
     * Prepare the command's synopsis and other metadata
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare()
    {
        $this->summary('Create a new model class')->description("## EXAMPLES \n\n wp kirki make:seeder DatabaseSeeder")->synopsis(Synopsis::type('positional')->name('seeder')->description('The seeder name'));
    }
}
