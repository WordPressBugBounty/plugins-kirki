<?php

/**
 * Marker contract requiring event listener classes to implement handle.
 * Keeps the event dispatch pipeline type-safe when resolving listeners.
 * Paired with EventManager and the Dispatchable trait on event classes.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Event
{
    /**
     * Handle.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function handle();
}
