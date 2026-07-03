<?php

/**
 * Scaffolding generator that creates a new PHP class file from a stub in the application path.
 * Accepts a class name and optional subdirectory, resolving namespaces from composer PSR-4 mappings.
 * Speeds up boilerplate creation for arbitrary application classes.
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
use function Kirki\Framework\app_path;
class MakeClassCommand extends CommandBase
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
        $this->output_dir = app_path();
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
        $this->create();
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
        return ['seeder' => Str::pascal($this->args[0]), 'namespace' => $this->namespace(), 'output_file' => \sprintf('%s/%s.php', $this->output_dir(), Str::pascal($this->args[0])), 'stub' => $this->get_stub()];
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
        if (File::missing($output_file)) {
            File::make_dir($output_file);
        }
        if (File::exists($output_file)) {
            $this->cli_error('Seeder file already exists.');
        }
        File::put($output_file, $content);
        $this->cli_success(\sprintf('Seeder [%s] created successfully.', $namespace . '\\' . $seeder));
    }
    /**
     * Get the output directory
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function output_dir()
    {
        return $this->assoc['folder'] ? $this->output_dir . '/' . $this->assoc['folder'] : $this->output_dir;
    }
    /**
     * Get the namespace for the class
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function namespace()
    {
        if (!empty($this->assoc['folder'])) {
            $folder = Str::split('/', $this->assoc['folder']);
            $folder = \array_map(function ($segment) {
                return Str::pascal($segment);
            }, $folder);
            return app()->qualify_app_namespace(\implode('/', $folder));
        }
        return app()->qualify_app_namespace();
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
        $stub_path = $this->stub_path() . '/class.stub';
        if (File::missing($stub_path)) {
            $this->cli_error('Class stub not found: ' . $stub_path);
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
        $this->summary('Create a new model class')->description("## EXAMPLES \n\n wp kirki make:class ExampleClass")->synopsis(Synopsis::type('positional')->name('classname')->description('The class name'))->synopsis(Synopsis::type('assoc')->name('folder')->description('The folder name')->optional());
    }
}
