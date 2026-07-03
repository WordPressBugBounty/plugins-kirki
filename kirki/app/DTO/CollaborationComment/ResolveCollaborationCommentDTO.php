<?php

namespace Kirki\App\DTO\CollaborationComment;

use Kirki\Framework\DTO;

class ResolveCollaborationCommentDTO extends DTO
{
    /** @var int */
    public $id;

    /** @var int */
    public $post_id;

    /** @var int */
    public $status = 1;

    /** @var string */
    public $session_id = '';

    /** @var int */
    public $user_id;
}
