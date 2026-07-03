<?php

namespace Kirki\App\DTO;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class GoogleFontDTO extends DTO 
{
    /** @var string */
    public $family;

    /** @var string */
    public $fontUrl;

    /** @var string */
    public $localUrl;

    /** @var array */
    public $files;
}