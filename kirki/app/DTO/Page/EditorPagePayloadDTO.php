<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class EditorPagePayloadDTO extends DTO 
{
    /** @var array */
    public $data;

    /** @var \Kirki\App\Models\Page */
    public $page;

    /** @var bool */
    public $is_staging = false;

    /** @var string|null */
    public $session_id = null;
}