<?php

/**
 * Trait giving event classes static dispatch, dispatch_if, and dispatch_unless helpers.
 * Instantiates the event and forwards it to the Event facade in one call.
 * Keeps domain events decoupled from the EventManager while preserving a fluent dispatch API.
 *
 * @package    Framework
 * @subpackage Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Concerns;

\defined('ABSPATH') || exit;
use Closure;
use Kirki\Framework\Supports\Facades\Event;
\defined('ABSPATH') || exit;
trait Dispatchable
{
    /**
     * Dispatch the event.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function dispatch()
    {
        return Event::dispatch(new static(...\func_get_args()));
    }
    /**
     * Dispatch the event if the boolean is true.
     *
     * @param mixed $boolean The boolean.
     * @param mixed $arguments The method arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function dispatch_if($boolean, ...$arguments)
    {
        $boolean = $boolean instanceof Closure ? $boolean() : $boolean;
        if ($boolean) {
            Event::dispatch(new static(...$arguments));
        }
    }
    /**
     * Dispatch the event unless the boolean is true.
     *
     * @param mixed $boolean The boolean.
     * @param mixed $arguments The method arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function dispatch_unless($boolean, ...$arguments)
    {
        $boolean = $boolean instanceof Closure ? $boolean() : $boolean;
        if (!$boolean) {
            Event::dispatch(new static(...$arguments));
        }
    }
}
