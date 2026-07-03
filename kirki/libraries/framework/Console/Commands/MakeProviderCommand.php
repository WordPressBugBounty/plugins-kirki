<?php

/**
 * Generates a service provider class stub in the application providers directory.
 * Creates register and boot method shells ready for container bindings.
 * Aligns new providers with the framework ServiceProvider contract.
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
class MakeProviderCommand extends CommandBase
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
        $this->output_dir = app_path('Providers');
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
        $provider_class = $data['provider_class'];
        $namespace = $data['namespace'];
        $output_file = $data['output_file'];
        $content = $this->populate_stub($data);
        if (File::exists($output_file)) {
            \WP_CLI::error('Request file already exists.');
        }
        File::put($output_file, $content);
        \WP_CLI::success(\sprintf('Request [%s] created successfully.', $namespace . "\\" . $provider_class));
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
        $provider_class = Str::pascal($this->args[0]);
        $data = ['stub' => $this->get_stub(), 'namespace' => app()->qualify_app_namespace('Providers'), 'provider_class' => $provider_class, 'output_file' => \sprintf('%s/%s.php', $this->output_dir, $provider_class)];
        $folder = $this->assoc['folder'] ?? null;
        if ($folder) {
            $data['namespace'] = \sprintf('%s\\%s', $data['namespace'], Str::pascal($folder));
            $data['output_file'] = \sprintf('%s/%s/%s.php', $this->output_dir, $folder, $provider_class);
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
        $stub_path = $this->stub_path() . '/provider.stub';
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
        $provider_class = $data['provider_class'];
        $namespace = $data['namespace'];
        return Str::replace(['{{class_name}}', '{{namespace}}'], [$provider_class, $namespace], $stub);
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
        $this->summary('Create a new provider class')->description("## EXAMPLES \n\n wp kirki make:provider ExampleServiceProvider")->synopsis(Synopsis::type('positional')->name('name')->description('The provider name'));
    }
}
