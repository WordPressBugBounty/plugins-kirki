<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class PagePayloadDTO extends DTO 
{
    /** @var string */
    public $post_title;

    /** @var string */
    public $post_type;

    /** @var array|null */
    public $blocks;

    /** @var array|null */
    public $conditions;

    /** @var string|null */
    public $collection_type;

    /** @var string|null */
    public $utility_page_type;

    /** @var array|null */
    public $custom_template;
}