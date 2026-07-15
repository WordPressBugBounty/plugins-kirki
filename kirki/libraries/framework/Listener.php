<?php

/**
 * Base class for event listeners defining a default priority of zero.
 * Subclasses override priority to control dispatch order relative to other listeners.
 * Keeps listener configuration minimal while supporting ordered execution.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
/**
 * Base class for event listeners defining a default priority of zero.
 * 
 * @method mixed handle(mixed $event)
 */
class Listener
{
    /**
     * The priority of the listener.
     * The default priority value is 0.
     * If you need to set more priority then increase the value on the
     * child classes. The higher the value the more the priority.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function priority()
    {
        return 0;
    }
}
