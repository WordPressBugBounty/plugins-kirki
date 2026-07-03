<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class PostStatus
{
    use HasConstants;

    const DRAFT = 'draft';
    const PUBLISH = 'publish';
    const FUTURE = 'future';
    const TRASH = 'trash';
    const AUTO_DRAFT = 'auto-draft';
}
