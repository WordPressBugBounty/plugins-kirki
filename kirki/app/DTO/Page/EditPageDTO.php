<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class EditPageDTO extends DTO 
{
    /** @var string */
    public $post_title;

    /** @var string */
    public $post_name;

    /** @var string */
    public $post_status;
}