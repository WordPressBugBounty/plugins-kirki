<?php

namespace Kirki\App\Broadcasting\Contracts;

defined('ABSPATH') || exit;

use Kirki\Framework\Collections\Collection;

interface Broadcaster
{
    /**
     * Publish collaboration actions to every other connected client.
     *
     * @param Collection $events  Collection<CreateCollaborationDTO>
     * @param bool       $cleanup Whether stale connections may be cleaned first.
     *
     * @return bool
     */
    public function broadcast(Collection $events, bool $cleanup = true);

    /**
     * Settings the browser needs to receive the published actions.
     *
     * @return array
     */
    public function client_config();
}
