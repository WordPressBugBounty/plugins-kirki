<?php

namespace Kirki\App\DTO\Media;

use Kirki\App\DTO\ListFilterDTO;

class MediaListFilterDTO extends ListFilterDTO
{
    /** @var string|null Comma separated categories */
    public $category;
}
