<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class PageMetaKeys
{
    use HasConstants;

    /**
     * @see KIRKI_META_NAME_FOR_STAGED_VERSIONS
     */
    const STAGED_VERSIONS = 'kirki_stage_versions';

    /**
     * @see KIRKI_GLOBAL_STYLE_BLOCK_META_KEY
     */
    const GLOBAL_STYLE_BLOCK_DEPRECATED = 'kirki_global_style_block';

    /**
     * Current Page Style Blocks
     * @see KIRKI_GLOBAL_STYLE_BLOCK_META_KEY_RANDOM
     */
    const STYLE_BLOCKS = 'kirki_global_style_block_random';

    /**
     * Used Global Style Block IDs
     * @see KIRKI_META_NAME_FOR_USED_STYLE_BLOCK_IDS
     */
    const USED_GLOBAL_STYLE_BLOCK_IDS = 'kirki_used_style_block_ids';

    /**
     * Used Current Page Style Block IDS
     * @see KIRKI_META_NAME_FOR_USED_STYLE_BLOCK_IDS_RANDOM
     */
    const USED_STYLE_BLOCK_IDS = 'kirki_used_style_block_ids_random';

    /**
     * @see KIRKI_META_NAME_FOR_USED_FONT_LIST
     */
    const USED_FONT_LIST = 'kirki_used_font_list';

    /**
     * @see 'kirki'
     */
    const BLOCKS = 'kirki';

    /**
     * @see KIRKI_META_NAME_FOR_POST_EDITOR_MODE
     */
    const EDITOR_MODE = 'kirki_editor_mode';

    const TEMPLATE_CONDITIONS = 'kirki_template_conditions';

    const TEMPLATE_COLLECTION_TYPE = 'kirki_template_collection_type';

    const UTILITY_PAGE_TYPE = 'kirki_utility_page_type';

    const PAGE_TEMPLATE = '_wp_page_template';

    /**
     * @see KIRKI_META_NAME_FOR_PAGE_HF_SYMBOL_DISABLE_STATUS
     */
    const DISABLED_PAGE_SYMBOLS = 'kirki_disabled_page_symbols';

    const VARIABLE_MODE ='kirki_variable_mode';

    const CUSTOM_CODE = 'kirki_page_custom_code';

    const SEO_SETTINGS = 'kirki_page_seo_settings';

    public static function get_single_post_keys()
    {
        return [
            static::VARIABLE_MODE,
            static::DISABLED_PAGE_SYMBOLS,
            static::TEMPLATE_CONDITIONS,
            static::TEMPLATE_COLLECTION_TYPE,
            static::UTILITY_PAGE_TYPE,
            static::BLOCKS,
            static::STYLE_BLOCKS,
            static::USED_FONT_LIST,
        ];
    }

}
