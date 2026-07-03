<?php

namespace Kirki\App\DTO\CollaborationComment;

use Kirki\Framework\DTO;

class CreateCollaborationCommentDTO extends DTO
{
    /** @var string */
    public $comment = '[]';

    /** @var int */
    public $post_id;

    /** @var int */
    public $parent_id = 0;

    /** @var int */
    public $status = 1;

    /** @var string */
    public $meta_data = '[]';

    /** @var string */
    public $session_id = '';

    /** @var int */
    public $user_id;
}
