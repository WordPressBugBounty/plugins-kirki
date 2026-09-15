<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Kirki\App\Broadcasting\BroadcastManager;
use Kirki\App\Constants\CollaborationConnectionType;
use Kirki\App\Constants\CollaborationParent;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\Models\CollaborationConnected;
use Kirki\Framework\Collections\Collection;

use Kirki\Framework\Constants\DateTimeFormats;
use function Kirki\Framework\collection;

class CollaborationService
{
	/**
	 * Broadcast a single collaboration action.
	 * 
	 * @param CreateCollaborationDTO $dto
	 * @param bool $cleanup
	 * 
	 * @return bool
	 */
	public function save_action(CreateCollaborationDTO $dto, bool $cleanup = true)
	{
		return $this->save_actions(collection([$dto]), $cleanup);
	}

	/**
	 * Broadcast a batch of collaboration actions.
	 * 
	 * @param Collection<CreateCollaborationDTO> $collection
	 * @param bool $cleanup
	 * 
	 * @return bool
	 */
	public function save_actions(Collection $collection, bool $cleanup = true)
	{
		if ($collection->is_empty()) {
			return false;
		}

		return (new BroadcastManager())->driver()->broadcast($collection, $cleanup);
	}

	/**
	 * Get all connected rows.
	 * first clean disconnected rows then get only recent connected rows.
	 * 
	 * @param bool $cleanup if true will clean disconnected rows.
	 * 
	 * @return Collection
	 */
	public function get_all_connected_rows(bool $cleanup = true)
	{
		if ($cleanup) {
			$this->clean_disconnected_rows();
		}

		return CollaborationConnected::all();
	}

	/**
	 * Clean disconnected rows if inactive less then 20 seconds.
	 * 
	 * @return bool
	 */
	public function clean_disconnected_rows()
	{
		$twenty_seconds_ago = gmdate(DateTimeFormats::DB_DATETIME, time() - 20);

		/** @var Collection $connections */
		$connections = CollaborationConnected::where('updated_at', '<=', $twenty_seconds_ago)->get();

		if ($connections->is_empty()) {
			return false;
		}

		// Step 1: Broadcast removal for each connection
		$remove_connections = $connections->map(function (CollaborationConnected $connection) {
			return CreateCollaborationDTO::from_array([
				'session_id' => $connection->session_id,
				'parent' => CollaborationParent::POST,
				'parent_id' => $connection->post_id,
				'data' => [
					'type' => CollaborationConnectionType::REMOVE_CONNECTION,
					'payload' => [
						'session_id' => $connection->session_id
					],
				],
			]);
		});

		$this->save_actions($remove_connections, false);

		// Step 2: Bulk delete all expired sessions in one query
		$session_ids = $connections->pluck('session_id')->to_array();

		CollaborationConnected::where_in('session_id', $session_ids)->delete();

		return true;
	}
}
