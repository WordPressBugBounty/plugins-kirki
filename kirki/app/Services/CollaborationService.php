<?php

namespace Kirki\App\Services;

use Kirki\App\Constants\CollaborationConnectionType;
use Kirki\App\Constants\CollaborationParent;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\Models\Collaboration;
use Kirki\App\Models\CollaborationConnected;
use Kirki\Framework\Collections\Collection;

use Kirki\Framework\Constants\DateTimeFormats;
use function Kirki\Framework\user;

defined('ABSPATH') || exit;

class CollaborationService
{
	/**
	 * Save collaboration data.
	 * 
	 * @param CreateCollaborationDTO $dto
	 * @param bool $cleanup
	 * 
	 * @return Collaboration|false
	 */
	public function save_action(CreateCollaborationDTO $dto, bool $cleanup = true)
	{
		$all_connected_rows = $this->get_all_connected_rows($cleanup);

		if ($all_connected_rows->count() <= 1) {
			return false;
		}

		return Collaboration::create([
			'user_id' => user()->get_id(),
			'session_id' => $dto->session_id,
			'parent' => $dto->parent ?? '',
			'parent_id' => $dto->parent_id,
			'data' => wp_json_encode($dto->data),
			'status' => $dto->status,
		]);
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

		// Get all expired connections (session_id + post_id in one query)
		/** @var Collection $connections */
		$connections = CollaborationConnected::where('updated_at', '<=', $twenty_seconds_ago)->get();

		if ($connections->is_empty()) {
			return false;
		}

		// Step 1: Broadcast removal for each connection
		$remove_connections = $connections->map(function (CollaborationConnected $connection) {
			$data = [
				'type' => CollaborationConnectionType::REMOVE_CONNECTION,
				'payload' => [
					'session_id' => $connection->session_id
				],
			];

			return CreateCollaborationDTO::from_array([
				'session_id' => $connection->session_id,
				'parent' => CollaborationParent::POST,
				'parent_id' => $connection->post_id,
				'data' => $data,
			])->to_array();
		});

		if ($remove_connections->not_empty()) {
			Collaboration::insert($remove_connections->to_array());
		}

		// Step 2: Bulk delete all expired sessions in one query
		$session_ids = $connections->pluck('session_id')->to_array();

		CollaborationConnected::where_in('session_id', $session_ids)->delete();

		return true;
	}
}