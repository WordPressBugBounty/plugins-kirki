<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class AccessLevels
{
    use HasConstants;

    const NO_ACCESS = 'no';
    const FULL_ACCESS = 'full';
    const EDITOR_ACCESS = 'editor';
    const CONTENT_ACCESS = 'content';
    const VIEW_ACCESS = 'view';

    /**
     * Hard-coded role → access level map. Any role not listed gets NO_ACCESS.
     */
    const ROLE_ACCESS_MAP = [
        'administrator' => self::FULL_ACCESS,
        'editor' => self::EDITOR_ACCESS,
        'author' => self::VIEW_ACCESS,
        'subscriber' => self::NO_ACCESS,
    ];
}
