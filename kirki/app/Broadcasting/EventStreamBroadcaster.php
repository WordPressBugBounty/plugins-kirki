<?php

namespace Kirki\App\Broadcasting;

defined('ABSPATH') || exit;

use Kirki\App\Broadcasting\Contracts\Broadcaster;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\Models\Collaboration;
use Kirki\App\Services\CollaborationService;
use Kirki\Framework\Collections\Collection;

use function Kirki\Framework\user;

/**
 * Stores collaboration actions in the database so the PHP event stream
 * endpoint can hand them to the other connected clients.
 */
class EventStreamBroadcaster implements Broadcaster
{
    public function __construct(array $config = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function broadcast(Collection $events, bool $cleanup = true)
    {
        $connections = (new CollaborationService())->get_all_connected_rows($cleanup);

        if ($connections->count() <= 1) {
            return false;
        }

        $rows = $events->map(function (CreateCollaborationDTO $dto) {
            return [
                'user_id'    => user()->get_id(),
                'session_id' => $dto->session_id,
                'parent'     => $dto->parent,
                'parent_id'  => $dto->parent_id,
                'data'       => wp_json_encode($dto->data),
                'status'     => $dto->status,
            ];
        })->to_array();

        return Collaboration::insert($rows);
    }

    /**
     * {@inheritDoc}
     */
    public function client_config()
    {
        return ['driver' => 'eventstream'];
    }
}
