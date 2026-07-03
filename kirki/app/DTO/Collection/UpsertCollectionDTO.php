<?php

namespace Kirki\App\DTO\Collection;

use Kirki\Framework\DTO;

class UpsertCollectionDTO extends DTO
{
    /** @var int|null */
    public $ID;

    /** @var string */
    public $post_title;

    /** @var string */
    public $post_name;

    /** @var array */
    public $fields;

    /** @var array */
    public $basic_fields;
}
