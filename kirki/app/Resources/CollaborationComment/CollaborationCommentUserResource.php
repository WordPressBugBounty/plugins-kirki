<?php

namespace Kirki\App\Resources\CollaborationComment;

use Kirki\Framework\Resource;
use function Kirki\Framework\user;

/**
 * Presentation for a WP_User in the @mention picker results.
 */
class CollaborationCommentUserResource extends Resource
{
    /**
     * Convert the user resource to an array.
     */
    public function to_array()
    {
        return [
            'user_id' => (int) $this->ID,
            'user_name' => $this->display_name,
            'user_avatar' => user($this->ID)->get_avatar(),
        ];
    }
}
