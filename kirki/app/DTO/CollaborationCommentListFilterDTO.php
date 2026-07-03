<?php

namespace Kirki\App\DTO;

class CollaborationCommentListFilterDTO extends ListFilterDTO
{
    /** @var int|null */
    public $post_id;

    public $user_id = 0;
}
