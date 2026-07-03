<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class TogglePageSymbolDTO extends DTO 
{
    /** @var int */
    public $post_id;

    /** @var string */
    public $symbol_type;

    /** @var bool */
    public $disable = false;
}