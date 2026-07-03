<?php

/**
 * Generates a controller class stub in the app Controllers directory.
 * Writes the file with the correct namespace and class name derived from the given argument.
 * Follows the same stub-and-write pattern as other make commands.
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
class MakeControllerCommand extends CommandBase
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
    protected $assoc = [];
    /**
     * The base path for the controller
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
        $this->output_dir = app_path('Http/Controllers');
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
        $content = $this->populate_stub($data);
        $output_file = $data['output_file'];
        if (File::exists($output_file)) {
            \WP_CLI::error('Controller file already exists.');
        }
        File::put($output_file, $content);
        $namespace = $data['namespace'];
        $controller = $data['controller'];
        \WP_CLI::success(\sprintf('Controller [%s\\%s] created successfully.', $namespace, $controller));
    }
    /**
     * Get the data for the controller
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function data()
    {
        $controller = Str::pascal($this->args[0]);
        $data = ['stub' => $this->get_stub(), 'controller' => $controller, 'namespace' => app()->qualify_app_namespace('Http/Controllers'), 'request_type_hint' => 'Request', 'output_file' => \sprintf('%s/%s.php', $this->output_dir, $controller)];
        if (!empty($this->assoc['api'])) {
            $data['namespace'] = app()->qualify_app_namespace('Http/Controllers/Api');
            $data['output_file'] = \sprintf('%s/API/%s.php', $this->output_dir, $controller);
        }
        return $data;
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
        $is_resource = $this->assoc['resource'] ?? \false;
        $stub_path = $is_resource ? $this->stub_path() . '/controllers/resource-controller.stub' : $this->stub_path() . '/controllers/controller.stub';
        if (File::missing($stub_path)) {
            \WP_CLI::error('Controller stub not found: ' . $stub_path);
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
        $namespace = $data['namespace'];
        $type_hint = $data['request_type_hint'];
        $controller = $data['controller'];
        $stub = $data['stub'];
        return Str::replace(['{{class_name}}', '{{namespace}}', '{{request_type_hint}}'], [$controller, $namespace, $type_hint], $stub);
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
        $this->summary('Create a new controller class')->description("## EXAMPLES \n\n wp kirki make:controller UserController")->synopsis(Synopsis::type('positional')->name('name')->description('The controller name'))->synopsis(Synopsis::type('flag')->name('api')->description('Create an API controller')->optional())->synopsis(Synopsis::type('flag')->name('resource')->description('Create a resource controller')->optional());
    }
}
