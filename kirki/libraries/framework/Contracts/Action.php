<?php

/**
 * Interface Action Represents a general-purpose action contract.
 * Any class implementing this interface should define the `handle` method,
 * which encapsulates the logic to be executed for the action.
 * This can be used for plugin lifecycle hooks, event listeners, jobs,
 * or any task-oriented class that needs a unified callable structure.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Action
{
    /**
     * Execute the action logic.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function handle();
}
