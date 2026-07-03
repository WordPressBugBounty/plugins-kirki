<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class PageSettingsDTO extends DTO 
{
    /** @var string */
    public $page_title;

    /** @var string */
    public $slug;

    /** @var string */
    public $page_description;

    /** @var string */
    public $post_status;

    /** @var string Featured image url */
    public $featured_image;

    /** @var array */
    public $seo_settings;

    /** @var array */
    public $custom_code;
}