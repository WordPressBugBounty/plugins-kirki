<?php

/**
 * Facade proxy for EventManager dispatch operations.
 * Exposes dispatch, dispatch_if, and dispatch_unless as static methods.
 * Used by the Dispatchable trait and manual event firing.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Closure;
use Kirki\Framework\Facade;
/**
 * Facade proxy for EventManager dispatch operations.
 * 
 * @method static void dispatch($event)
 * @method static void dispatch_if(Closure $boolean, $event)
 * @method static void dispatch_unless(Closure $boolean, $event)
 * @see    \Framework\Core\Managers\EventManager
 */
class Event extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'event';
    }
}
