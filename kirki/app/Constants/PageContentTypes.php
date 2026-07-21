<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class PageContentTypes {
    use HasConstants;

    const STYLES = 'styles';
    const BLOCKS = 'blocks';
    const USED_STYLES = 'used-styles'; // Global styles
    const USED_STYLE_IDS_RANDOM = 'used-style-ids-random'; // Current Page style ids
    const USED_FONTS = 'used-fonts';
    const CUSTOM_FONTS = 'custom-fonts';
    const VIEWPORT_LIST = 'viewport-list';
}