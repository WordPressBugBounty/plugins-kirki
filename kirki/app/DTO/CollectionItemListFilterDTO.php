<?php

namespace Kirki\App\DTO;

use Kirki\Framework\DTO;

class CollectionItemListFilterDTO extends DTO
{
    /** @var int */
    public $post_parent;

    /** @var int */
    public $page = 1;

    /** @var int */
    public $limit = 20;

    /** @var string */
    public $query = '';

    /** @var array */
    public $exclude_post_ids = [];

    /** @var string */
    public $post_status = 'all';

    /** @var string */
    public $post_date = 'all';

    /** @var string */
    public $post_modified = 'all';
}
