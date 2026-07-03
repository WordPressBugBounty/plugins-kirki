<?php

namespace Kirki\App\DTO\Apps;

use Kirki\Framework\DTO;

defined('ABSPATH') || exit;

class InstalledAppDTO extends DTO
{
    /** @var string */
    public $version;

    /** @var string */
    public $app_slug;
}