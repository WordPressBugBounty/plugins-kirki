<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class EditorPagePayloadDTO extends DTO 
{
    /** 
     * @var array
     * array keys : blocks, styles, usedStyles, usedStyleIdsRandom, usedFonts
     */
    public $data;

    /** @var \Kirki\App\Models\Page */
    public $page;

    /** @var bool */
    public $is_staging = false;
}