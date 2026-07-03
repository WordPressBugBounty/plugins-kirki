<?php

/**
 * Scaffolds a form request class with validation rule placeholders.
 * Places the file under app Requests with the resolved namespace.
 * Supports the request validation pipeline used by controllers and DTOs.
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
class MakeRequestCommand extends CommandBase
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
     * The base path for the request
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
        $this->output_dir = app_path('Http/Requests');
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
        $request_class = $data['request_class'];
        $namespace = $data['namespace'];
        $output_file = $data['output_file'];
        $content = $this->populate_stub($data);
        if (File::exists($output_file)) {
            \WP_CLI::error('Request file already exists.');
        }
        File::put($output_file, $content);
        \WP_CLI::success(\sprintf('Request [%s] created successfully.', $namespace . "\\" . $request_class));
    }
    /**
     * Make the data for the request
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function data()
    {
        $request_class = Str::pascal($this->args[0]);
        $data = ['stub' => $this->get_stub(), 'namespace' => app()->qualify_app_namespace('Http/Requests'), 'request_class' => $request_class, 'output_file' => \sprintf('%s/%s.php', $this->output_dir, $request_class)];
        $folder = $this->assoc['folder'] ?? null;
        if ($folder) {
            $data['namespace'] = \sprintf('%s\\%s', $data['namespace'], Str::pascal($folder));
            $data['output_file'] = \sprintf('%s/%s/%s.php', $this->output_dir, $folder, $request_class);
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
        $stub_path = $this->stub_path() . '/request.stub';
        if (File::missing($stub_path)) {
            \WP_CLI::error('Request stub not found: ' . $stub_path);
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
        $stub = $data['stub'];
        $request_class = $data['request_class'];
        $namespace = $data['namespace'];
        return Str::replace(['{{class_name}}', '{{namespace}}'], [$request_class, $namespace], $stub);
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
        $this->summary('Create a new request class')->description("## EXAMPLES \n\n wp kirki make:request UserRequest")->synopsis(Synopsis::type('positional')->name('name')->description('The request name'))->synopsis(Synopsis::type('assoc')->name('folder')->description('The request folder')->optional());
    }
}
