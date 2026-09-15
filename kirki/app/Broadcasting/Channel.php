<?php

namespace Kirki\App\Broadcasting;

defined('ABSPATH') || exit;

use Kirki\App\Constants\CollaborationParent;

/**
 * Channel naming shared by the server and the browser.
 */
class Channel
{
    /**
     * Name of the event every collaboration action is published as.
     */
    const EVENT = 'kirki.collaboration';

    /**
     * Build the channel name of a collaboration parent.
     *
     * Post channels are presence channels so the connected user list comes
     * from the socket server itself. Everything else is private.
     *
     * @param string $parent
     * @param int    $parent_id
     *
     * @return string
     */
    public static function name($parent, $parent_id)
    {
        $prefix = CollaborationParent::POST === $parent ? 'presence-' : 'private-';

        return $prefix . 'kirki-collaboration-' . $parent . '-' . (int) $parent_id;
    }

    /**
     * @param string $channel
     *
     * @return bool
     */
    public static function is_presence($channel)
    {
        return 0 === strpos($channel, 'presence-');
    }
}
