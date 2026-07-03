<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class CollaborationConnectionType
{
    use HasConstants;

    const UPDATE_GLOBAL_STYLE = 'COLLABORATION_UPDATE_GLOBAL_STYLE';
    const REMOVE_CONNECTION = 'COLLABORATION_REMOVE_CONNECTION';

}
