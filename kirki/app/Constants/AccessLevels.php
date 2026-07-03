<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class AccessLevels
{
    use HasConstants;

    const NO_ACCESS = 'no';
    const FULL_ACCESS = 'full';
    const CONTENT_ACCESS = 'content';
    const VIEW_ACCESS = 'view';

    const USERS_DEFAULT_FULL_ACCESS = ['administrator', 'editor'];
}
