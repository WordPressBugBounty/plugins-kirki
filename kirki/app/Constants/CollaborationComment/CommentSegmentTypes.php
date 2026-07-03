<?php

namespace Kirki\App\Constants\CollaborationComment;

use Kirki\Framework\Concerns\HasConstants;

defined('ABSPATH') || exit;

class CommentSegmentTypes
{
    use HasConstants;

    const TEXT = 'text';
    const MENTION = 'mention';
    const NEWLINE = 'newline';
}
