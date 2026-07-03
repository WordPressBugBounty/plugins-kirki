<?php

/**
 * Abstract base for WP-CLI commands registered through the framework.
 * Defines synopsis, description, and lifecycle hooks including prepare and run.
 * Normalizes argument handling so concrete commands focus on business logic rather than CLI plumbing.
 *
 * @package    Framework
 * @subpackage Console
 * @since      1.0.0
 */
namespace Kirki\Framework\Console;

\defined('ABSPATH') || exit;
use Kirki\Framework\Collections\Collection;
use function Kirki\Framework\collection;
abstract class CommandBase
{
    /**
     * The summary.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $summary;
    /**
     * The description.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $description;
    /**
     * The args.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args = [];
    /**
     * The when.
     *
     * @var 'after_wp_load'
     *
     * @since 1.0.0
     */
    protected $when;
    /**
     * The synopsis.
     *
     * @var Collection<Synopsis>
     *
     * @since 1.0.0
     */
    protected $synopsis;
    /**
     * Initialize the command
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->synopsis = collection();
        $this->prepare();
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
    protected abstract function run($args, $assoc);
    /**
     * Prepare the command
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare()
    {
        //
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
        return \true;
    }
    /**
     * Get the stub path
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function stub_path()
    {
        return __DIR__ . '/stubs';
    }
    /**
     * Cli error.
     *
     * @param string $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function cli_error(string $message) : void
    {
        \call_user_func(['\\WP_CLI', 'error'], $message);
    }
    /**
     * Cli success.
     *
     * @param string $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function cli_success(string $message) : void
    {
        \call_user_func(['\\WP_CLI', 'success'], $message);
    }
    /**
     * Cli line.
     *
     * @param string $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function cli_line(string $message) : void
    {
        \call_user_func(['\\WP_CLI', 'line'], $message);
    }
    /**
     * Set the command summary
     *
     * @param mixed $summary The summary.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    protected function summary($summary)
    {
        $this->summary = $summary;
        return $this;
    }
    /**
     * Set the command description
     *
     * @param mixed $description The description.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    protected function description($description)
    {
        $this->description = $description;
        return $this;
    }
    /**
     * Set the command synopsis
     *
     * @param Synopsis $synopsis The synopsis.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    protected function synopsis(Synopsis $synopsis)
    {
        $this->synopsis->push($synopsis);
        return $this;
    }
    /**
     * Set the command when
     *
     * @param mixed $when The when.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    protected function when($when)
    {
        $this->when = $when;
        return $this;
    }
    /**
     * Get the command arguments
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function args()
    {
        $args = [];
        if ($this->summary) {
            $args['shortdesc'] = $this->summary;
        }
        if ($this->description) {
            $args['longdesc'] = $this->description;
        }
        if (!$this->synopsis->empty()) {
            $args['synopsis'] = $this->synopsis->map(function (Synopsis $synopsis) {
                return $synopsis->to_array();
            })->all();
        }
        return $args;
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
    public function __invoke($args, $assoc)
    {
        if (!$this->passed($args, $assoc)) {
            $this->cli_error('Command failed to pass validation. Please check the arguments and try again.');
        }
        $start = \microtime(\true);
        $this->run($args, $assoc);
        $runtime = \number_format((\microtime(\true) - $start) * 1000);
        $this->cli_line(\sprintf("Time: %sms", $runtime));
    }
}
