<?php

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class EditPopupDTO extends DTO 
{
    /** @var array */
    public $blocks;

    /** @var array */
    public $styleBlocks;

    /** @var array */
    public $usedFonts;
}