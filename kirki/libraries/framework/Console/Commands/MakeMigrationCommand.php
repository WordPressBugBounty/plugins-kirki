<?php

/**
 * Creates a timestamped migration file in the database migrations folder.
 * Uses a stub template and converts the supplied name to snake_case for the filename.
 * Integrates with the migrator by placing files where MigrationRepository discovers them.
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
class MakeMigrationCommand extends CommandBase
{
    /**
     * Command's positional arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args;
    /**
     * Command's associative arguments
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $assoc;
    /**
     * Migration base path
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
        $this->output_dir = database_path('migrations');
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
        $this->summary('Create a new migration file')->description("## EXAMPLES \n\n wp kirki make:migration create_users_table")->synopsis(Synopsis::type('positional')->name('name')->description('The migration name. The name should starts with `create_` and ends with `table`'))->synopsis(Synopsis::type('assoc')->name('prefix')->description('Table prefix (if any)')->optional());
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
     * Get data for migration file
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function data()
    {
        return ['stub' => $this->get_stub(), 'table' => $this->table($this->args[0]), 'migration_class' => $this->migration_class($this->args[0]), 'output_file' => \sprintf('%s/%s.php', $this->output_dir, $this->migration_class($this->args[0]))];
    }
    /**
     * Get migration stub content
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function get_stub()
    {
        $stub_path = $this->stub_path() . '/migration.stub';
        if (File::missing($stub_path)) {
            \WP_CLI::error('Migration stub not found: ' . $stub_path);
        }
        return File::get($stub_path);
    }
    /**
     * Create migration file
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function create()
    {
        $data = $this->data();
        $migration_class = $data['migration_class'];
        $output_file = $data['output_file'];
        if (File::exists($output_file)) {
            \WP_CLI::error('Migration file already exists.');
        }
        $content = $this->populate_stub($data);
        File::put($output_file, $content);
        \WP_CLI::success(\sprintf('Migration  [%s] created.', $migration_class));
    }
    /**
     * Get table name from filename
     *
     * @param mixed $filename The filename.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function table($filename)
    {
        $table = Str::replace(['create_', '_table'], '', $filename);
        $prefix = $this->assoc['prefix'] ?? app()->prefix();
        if ($prefix) {
            return \sprintf('%s_%s', Str::snake(Str::trim($prefix, '_')), $table);
        }
        return $table;
    }
    /**
     * Get migration class name from filename
     *
     * @param mixed $filename The filename.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function migration_class($filename)
    {
        return Str::pascal($filename);
    }
    /**
     * Populate stub content
     *
     * @param mixed $data The data payload.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function populate_stub($data)
    {
        return Str::replace(['{{class_name}}', '{{table}}', '{{migrations_namespace}}'], [$data['migration_class'], $data['table'], app()->get_migrations_namespace()], $data['stub']);
    }
}
