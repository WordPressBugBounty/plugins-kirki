<?php

namespace Kirki\App\DTO\CollaborationComment;

use Kirki\Framework\DTO;
use WP_User;

class DeleteCollaborationCommentDTO extends DTO
{
    /** @var int */
    public $id;

    /** @var int */
    public $post_id;

    /** @var string */
    public $session_id = '';
}
