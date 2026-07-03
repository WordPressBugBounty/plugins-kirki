<?php

/**
 * Base hook subscriber with sensible defaults for WordPress action and filter registration.
 * Implements HookSubscriber with standard priority and accepted-args values for concrete hook classes.
 * Reduces boilerplate when wiring framework integrations into WordPress lifecycle events.
 * Subclasses must implement: - get_name() - get_type() - handle()
 * This class also defines constants for hook types: ACTION and FILTER.
 *
 * @package    Framework
 * @subpackage Wordpress
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress;

\defined('ABSPATH') || exit;
use Kirki\Framework\Wordpress\Contracts\HookSubscriber;
abstract class BaseHook implements HookSubscriber
{
    /**
     * Get the priority for this hook. Can be overridden by subclasses.
     *
     * @return int Hook priority. Lower numbers correspond to earlier execution.
     *
     * @since 1.0.0
     */
    public function get_priority()
    {
        return 10;
    }
    /**
     * Get the number of arguments accepted by the hook callback.
     * Can be overridden by subclasses.
     *
     * @return int Number of accepted arguments.
     *
     * @since 1.0.0
     */
    public function get_args_count()
    {
        return 1;
    }
    /**
     * Get the name.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public abstract function get_name();
    /**
     * Get the type.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public abstract function get_type();
    /**
     * Handle.
     *
     * @param mixed $args The positional arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public abstract function handle(...$args);
}
