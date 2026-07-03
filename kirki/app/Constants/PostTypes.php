<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class PostTypes
{
    use HasConstants;

    /**
     * @see KIRKI_GLOBAL_DATA_POST_TYPE_NAME
     */
    const GLOBAL_DATA = 'kirki_global_data';

    /**
     * @see KIRKI_POST_TYPE
     */
    const COLLECTION = 'kirki_post';

    /**
     * @see KIRKI_SYMBOL_TYPE
     */
    const SYMBOL = 'kirki_symbol';

    /**
     * @see KIRKI_CONTENT_MANAGER_PREFIX
     */
    const CONTENT_MANAGER = 'kirki_cm';

    const WP_PAGE = 'page';

    const WP_POST = 'post';

    const POPUP = 'kirki_popup';

    const TEMPLATE = 'kirki_template';

    const UTILITY = 'kirki_utility';

    const PAGE = 'kirki_page';

    const WP_ATTACHMENT = 'attachment';
}
