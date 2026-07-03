<?php

namespace Kirki\App\Constants\CollaborationComment;

use Kirki\Framework\Concerns\HasConstants;

defined('ABSPATH') || exit;

class CollaborationCommentResolveStatus
{
    use HasConstants;

    const UNRESOLVED = 1;
    const RESOLVED = 2;
}
