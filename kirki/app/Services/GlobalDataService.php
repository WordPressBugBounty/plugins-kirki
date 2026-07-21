<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Kirki\App\Constants\CollaborationConnectionType;
use Kirki\App\Constants\CollaborationParent;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\Supports\Facades\GlobalData;

class GlobalDataService 
{
    /**
     * Save global styles
     * 
     * @return array
     */
    public function save_styles(array $global_styles, ?string $session_id = null) 
    {
		foreach ($global_styles as $key => $style_block) {
			if (empty($style_block['isGlobalStyle'])) {
				$global_styles[$key] = array_merge($style_block, ['isGlobalStyle' => true]);
			}
		}

		if ($session_id) {
			$create_collaboration_dto = CreateCollaborationDTO::from_array([
				'session_id' => $session_id,
				'parent' => CollaborationParent::GLOBAL,
				'parent_id' => 0,
				'data' => [
					'type'    => CollaborationConnectionType::UPDATE_GLOBAL_STYLE,
					'payload' => ['styleBlock' => $global_styles],
				],
			]);

			(new CollaborationService())->save_action($create_collaboration_dto);
		}

		GlobalData::update_style_blocks($global_styles);

		return $global_styles;
    }
}