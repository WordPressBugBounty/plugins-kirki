<?php

namespace Kirki\App\DTO;

use Kirki\App\Constants\PostStatus;
use Kirki\Framework\DTO;

class UpsertCollectionItemDTO extends DTO
{
    /** @var int|null */
    public $ID;

    /** @var int */
    public $post_parent;

    /** @var string */
    public $post_title;

    /** @var string */
    public $post_name;

    /** @var string */
    public $post_status = PostStatus::DRAFT;

    /** @var \DateTimeInterface|string|null */
    public $post_date;

    /** @var array */
    public $fields;
}
