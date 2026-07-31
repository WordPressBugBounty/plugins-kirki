<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class UserMetaKeys
{
    use HasConstants;

    const CAPABILITIES = 'wp_capabilities';

    /**
     * @see KIRKI_USER_WALKTHROUGH_SHOWN_META_KEY
     */
    const WALKTHROUGH_SHOWN_STATE = 'user_walkthrough_shown_state';
}
