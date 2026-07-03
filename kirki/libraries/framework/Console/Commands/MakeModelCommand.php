<?php

/**
 * Scaffolds an Eloquent-style model class extending the framework Model base.
 * Outputs to the app Models path with fillable and table conventions from the stub.
 * Reduces repetitive model setup for new database entities.
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
class MakeModelCommand extends CommandBase
{
    /**
     * The arguments for the command
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args;
    /**
     * The options for the command
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
        $this->output_dir = app_path('Models');
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
     * Data.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function data()
    {
        return ['model' => Str::pascal($this->args[0]), 'namespace' => app()->qualify_app_namespace('Models'), 'output_file' => \sprintf('%s/%s.php', $this->output_dir, Str::pascal($this->args[0])), 'stub' => $this->get_stub()];
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
        $stub = $data['stub'];
        $model = $data['model'];
        $output_file = $data['output_file'];
        $content = $this->populate_stub($model, $stub);
        if (\file_exists($output_file)) {
            \WP_CLI::error('Model file already exists.');
        }
        \file_put_contents($output_file, $content);
        \WP_CLI::success(\sprintf('Model App\\Models\\[%s] created successfully.', $model));
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
        $stub_path = $this->stub_path() . '/model.stub';
        if (File::missing($stub_path)) {
            \WP_CLI::error('Model stub not found: ' . $stub_path);
        }
        return File::get($stub_path);
    }
    /**
     * Populate the stub content
     *
     * @param mixed $model The model instance.
     * @param mixed $stub The stub.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function populate_stub($model, $stub)
    {
        return Str::replace(['{{class_name}}', '{{namespace}}'], [$model, app()->qualify_app_namespace('Models')], $stub);
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
        $this->summary('Create a new model class')->description("## EXAMPLES \n\n wp kirki make:model User")->synopsis(Synopsis::type('positional')->name('model')->description('The model name'));
    }
}
