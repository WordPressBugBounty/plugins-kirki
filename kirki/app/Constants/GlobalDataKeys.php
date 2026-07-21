<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class GlobalDataKeys
{
    use HasConstants;

    const EDITOR_READ_ONLY_ACCESS_STATUS = 'kirki_editor_read_only_access_status';
    const EDITOR_READ_ONLY_ACCESS_TOKEN = 'kirki_editor_read_only_access_token';
    const INSTALLED_APPS = 'kirki_installed_apps';
    const APP_SETTINGS = 'kirki_app_settings_';

    /**
     * @see KIRKI_GLOBAL_STYLE_BLOCK_META_KEY
     */
    const STYLE_BLOCK_DEPRECATED = 'kirki_global_style_block';

    const STYLE_BLOCKS = 'kirki_class_manager_style_blocks';

    /**
     * @see KIRKI_USER_CUSTOM_FONTS_META_KEY
     */
    const CUSTOM_FONTS = 'kirki_user_custom_fonts';

    /**
     * @see KIRKI_USER_CONTROLLER_META_KEY
     */
    const UI_CONTROLLER = 'kirki_user_controller';


    /**
     * @see KIRKI_USER_SAVED_DATA_META_KEY
     */
    const UI_SAVED_DATA = 'kirki_user_saved_data';
}
