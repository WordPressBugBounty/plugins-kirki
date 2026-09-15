<?php

namespace Kirki\App\Broadcasting;

defined('ABSPATH') || exit;

use Kirki\App\Broadcasting\Contracts\Broadcaster;

use function Kirki\Framework\config;

/**
 * Resolves the broadcaster configured in config/broadcasting.php.
 */
class BroadcastManager
{
    /**
     * @var Broadcaster|null
     */
    protected $driver;

    /**
     * Available drivers, keyed by their configuration name.
     *
     * @return array
     */
    protected function drivers()
    {
        return apply_filters(
            'kirki_broadcast_drivers',
            [
                'pusher'      => PusherBroadcaster::class,
                'eventstream' => EventStreamBroadcaster::class,
            ]
        );
    }

    /**
     * @return Broadcaster
     */
    public function driver()
    {
        if ($this->driver) {
            return $this->driver;
        }

        $name    = config('broadcasting.default', 'eventstream');
        $config  = config("broadcasting.connections.{$name}", []);
        $drivers = $this->drivers();
        $class   = isset($drivers[$name]) ? $drivers[$name] : EventStreamBroadcaster::class;

        $this->driver = new $class($config);

        return $this->driver;
    }
}
