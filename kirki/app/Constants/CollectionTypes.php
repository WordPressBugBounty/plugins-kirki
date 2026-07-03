<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class CollectionTypes
{
    use HasConstants;

    const POST = 'post';
    const TERM = 'term';
    const USER = 'user';
}
