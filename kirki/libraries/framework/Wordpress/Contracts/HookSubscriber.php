<?php

/**
 * Interface HookSubscriber Defines the contract for a 
 * class that can subscribe to a WordPress hook (action or filter) and handle it with optional configuration.
 * Implementing classes should define which hook they subscribe to, 
 * the type of the hook (action or filter), and optionally the priority and argument count.
 *
 * @package    Framework
 * @subpackage Wordpress\Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Contracts;

\defined('ABSPATH') || exit;
interface HookSubscriber
{
    /**
     * Get the name of the WordPress hook this subscriber listens to.
     *
     * @return string The hook name (e.g., 'init', 'rest_api_init', etc.)
     *
     * @since 1.0.0
     */
    public function get_name();
    /**
     * Get the type of the hook: 'action' or 'filter'.
     *
     * @return string Either 'action' or 'filter'.
     *
     * @since 1.0.0
     */
    public function get_type();
    /**
     * Get the priority at which the hook should be fired.
     *
     * @return int The priority level (default is 10).
     *
     * @since 1.0.0
     */
    public function get_priority();
    /**
     * Get the number of arguments the hook callback accepts.
     *
     * @return int Number of accepted arguments.
     *
     * @since 1.0.0
     */
    public function get_args_count();
    /**
     * Handle the hook logic. Will be called when the hook is fired.
     *
     * @param mixed $args The positional arguments.
     *
     * @return mixed Return value is only used for filters. Actions can return void.
     *
     * @since 1.0.0
     */
    public function handle(...$args);
}
