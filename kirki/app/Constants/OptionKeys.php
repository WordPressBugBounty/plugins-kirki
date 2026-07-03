<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class OptionKeys
{
    use HasConstants;

    const MIGRATIONS = 'kirki_database_migrations'; // @todo: will be handled later
    const GLOBAL_DATA_POST_TYPE_ID = 'KIRKI_GLOBAL_DATA_POST_TYPE_ID';
    const DROIP_GLOBAL_DATA_POST_TYPE_ID = 'DROIP_GLOBAL_DATA_POST_TYPE_ID'; // @todo: will be removed if not needed
    const PAGE_ON_FRONT = 'page_on_front';
}
