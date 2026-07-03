<?php
/**
 * Kirki Configurations
 *
 * @package kirki
 */

defined('ABSPATH') || exit;

if(!defined('KIRKI_APP_PREFIX')){
	define( 'KIRKI_APP_PREFIX', 'kirki' );
}
if (!defined('KIRKI_PREFIX')) {
	define('KIRKI_PREFIX', 'kirki_');
}
if (!defined('KIRKI_ENV_MODE')) {
	define('KIRKI_ENV_MODE', 'production');
}
if (!defined('KIRKI_CORE_PLUGIN_URL')) {
	define( 'KIRKI_CORE_PLUGIN_URL', 'https://kirki.com' );
}
if (!defined('KIRKI_PUBLIC_ASSETS_URL')) {
	define( 'KIRKI_PUBLIC_ASSETS_URL', 'https://d31d7414w5c76z.cloudfront.net' );
}

if (!defined('KIRKI_META_NAME_FOR_POST_EDITOR_MODE')) {
	define( 'KIRKI_META_NAME_FOR_POST_EDITOR_MODE', 'kirki_editor_mode' );
}
if (!defined('KIRKI_META_NAME_FOR_USED_STYLE_BLOCK_IDS')) {
	define( 'KIRKI_META_NAME_FOR_USED_STYLE_BLOCK_IDS', 'kirki_used_style_block_ids' );
}
if (!defined('KIRKI_META_NAME_FOR_USED_FONT_LIST')) {
	define( 'KIRKI_META_NAME_FOR_USED_FONT_LIST', 'kirki_used_font_list' );
}
if (!defined('KIRKI_META_NAME_FOR_STAGED_VERSIONS')) {
	define( 'KIRKI_META_NAME_FOR_STAGED_VERSIONS', 'kirki_stage_versions' );
}
if (!defined('KIRKI_META_NAME_FOR_PAGE_HF_SYMBOL_DISABLE_STATUS')) {
	define( 'KIRKI_META_NAME_FOR_PAGE_HF_SYMBOL_DISABLE_STATUS', 'kirki_disabled_page_symbols' );
}


// droip apps.
if (!defined('IS_DEVELOPING_KIRKI_APPS')) {
	define( 'IS_DEVELOPING_KIRKI_APPS', false );
}
if ( IS_DEVELOPING_KIRKI_APPS ) {
	if (!defined('KIRKI_APPS_BASE_URL')) {
		define( 'KIRKI_APPS_BASE_URL', content_url() . '/kirki-apps-dev/build' );
	}
} else {
	if (!defined('KIRKI_APPS_BASE_URL')) {
		define( 'KIRKI_APPS_BASE_URL', KIRKI_PUBLIC_ASSETS_URL . '/kirki-apps' );
	}
}
if (!defined('KIRKI_DEVELOPING_APPS_INCLUDES')) {
	define( 'KIRKI_DEVELOPING_APPS_INCLUDES', plugin_dir_path( __FILE__ ) . '../../kirki-apps-dev/index.php' );
}


/* all types of file paths start */
if (!defined('KIRKI_PLUGIN_PATH')) {
	define('KIRKI_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('KIRKI_PLUGIN_REL_URL')) {
	define( 'KIRKI_PLUGIN_REL_URL', dirname( plugin_basename( __FILE__ ) ) );
}
if (!defined('KIRKI_ROOT_URL')) {
	define( 'KIRKI_ROOT_URL', plugin_dir_url( __FILE__ ) );
}
if (!defined('KIRKI_ASSETS_URL')) {
	define( 'KIRKI_ASSETS_URL', KIRKI_ROOT_URL . 'assets/' );
}
if (!defined('KIRKI_PLUGIN_FILE')) {
	define('KIRKI_PLUGIN_FILE', KIRKI_PLUGIN_PATH . 'kirki.php');
}
if (!defined('KIRKI_FULL_CANVAS_TEMPLATE_PATH')) {
	define( 'KIRKI_FULL_CANVAS_TEMPLATE_PATH', str_replace( '\\', '/', KIRKI_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'page.php' ) );
}
/* all types of file paths end */

/* all types of post types start */
if (!defined('KIRKI_GLOBAL_DATA_POST_TYPE_NAME')) {
	define( 'KIRKI_GLOBAL_DATA_POST_TYPE_NAME','kirki_global_data' );
}
if (!defined('KIRKI_POST_TYPE')) {
	define( 'KIRKI_POST_TYPE','kirki_post' );
}
if (!defined('KIRKI_SYMBOL_TYPE')) {
	define( 'KIRKI_SYMBOL_TYPE','kirki_symbol' );
}
if (!defined('KIRKI_CONTENT_MANAGER_PREFIX')) {
	define( 'KIRKI_CONTENT_MANAGER_PREFIX','kirki_cm' );
}
/* all types of post types end */

/* all types of option meta types start */
if (!defined('KIRKI_USER_CONTROLLER_META_KEY')) {
	define( 'KIRKI_USER_CONTROLLER_META_KEY','kirki_user_controller' );
}
if (!defined('KIRKI_GLOBAL_STYLE_BLOCK_META_KEY')) {
	define( 'KIRKI_GLOBAL_STYLE_BLOCK_META_KEY','kirki_global_style_block' );
}
if (!defined('KIRKI_USER_SAVED_DATA_META_KEY')) {
	define( 'KIRKI_USER_SAVED_DATA_META_KEY','kirki_user_saved_data' );
}
if (!defined('KIRKI_USER_CUSTOM_FONTS_META_KEY')) {
	define( 'KIRKI_USER_CUSTOM_FONTS_META_KEY','kirki_user_custom_fonts' );
}
if (!defined('KIRKI_PAGE_SEO_SETTINGS_META_KEY')) {
	define( 'KIRKI_PAGE_SEO_SETTINGS_META_KEY','kirki_page_seo_settings' );
}
if (!defined('KIRKI_PAGE_CUSTOM_CODE')) {
	define( 'KIRKI_PAGE_CUSTOM_CODE','kirki_page_custom_code' );
}

if (!defined('KIRKI_WP_ADMIN_COMMON_DATA')) {
	define( 'KIRKI_WP_ADMIN_COMMON_DATA','kirki_wp_admin_common_data' );
}
/* all types of option meta types end */

/* all types of user meta types start */
if (!defined('KIRKI_USER_WALKTHROUGH_SHOWN_META_KEY')) {
	define( 'KIRKI_USER_WALKTHROUGH_SHOWN_META_KEY','kirki_user_walkthrough_shown_state' );
}
/* all types of user meta types end */

if (!defined('KIRKI_EDITOR_ACTION')) {
	define( 'KIRKI_EDITOR_ACTION', 'kirki' );
}

if (!defined('KIRKI_ACCESS_LEVELS')) {
	define(
		'KIRKI_ACCESS_LEVELS',
		array(
			'NO_ACCESS'      => 'no',
			'FULL_ACCESS'    => 'full',
			'CONTENT_ACCESS' => 'content',
			'VIEW_ACCESS'    => 'view',
		)
	);
}
if (!defined('KIRKI_USERS_DEFAULT_FULL_ACCESS')) {
	define( 'KIRKI_USERS_DEFAULT_FULL_ACCESS', array( 'administrator', 'editor' ) );
}

/* Custom DB table names */
if (!defined('KIRKI_FORM_TABLE')) {
	define( 'KIRKI_FORM_TABLE','kirki_forms' );
}
if (!defined('KIRKI_FORM_DATA_TABLE')) {
	define( 'KIRKI_FORM_DATA_TABLE','kirki_forms_data' );
}
if (!defined('KIRKI_COLLABORATION_TABLE')) {
	define( 'KIRKI_COLLABORATION_TABLE','kirki_collaborations' );
}
if (!defined('KIRKI_CM_REFERENCE_TABLE')) {
	define( 'KIRKI_CM_REFERENCE_TABLE','kirki_cm_reference' );
}
if (!defined('KIRKI_COMMENTS_TABLE')) {
	define( 'KIRKI_COMMENTS_TABLE','kirki_comments' );
}
/* Custom DB table names */

if (!defined('KIRKI_SUPPORTED_MEDIA_TYPES')) {
	define(
		'KIRKI_SUPPORTED_MEDIA_TYPES',
		array(
			'image'  => array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ),
			'video'  => array( 'video/mp4', 'video/ogg', 'video/quicktime' ),
			'svg'    => array( 'image/svg+xml' ),
			'audio'  => array( 'audio/mpeg', 'audio/ogg' ),
			'json'   => array( 'application/json' ),
			'lottie' => array( 'text/plain', 'application/json' ),
			'pdf'    => array( 'application/pdf' ),
		)
	);
}


if (!defined('KIRKI_SUPPORTED_MEDIA_TYPES_FOR_FILE_INPUT')) {
	define(
		'KIRKI_SUPPORTED_MEDIA_TYPES_FOR_FILE_INPUT',
		array(
			'default'                 => array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/ogg', 'video/quicktime', 'application/pdf', 'application/msword', 'text/plain' ),
			'.doc, .pdf, .txt'        => array( 'application/pdf', 'application/msword', 'text/plain' ),
			'.mp4, .mov'              => array( 'video/mp4', 'video/ogg', 'video/quicktime' ),
			'.jpg, .jpeg, .png, .gif' => array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ),
		)
	);
}

if (!defined('KIRKI_WORDPRESS_SORT_BY_OPTIONS')) {
	define(
		'KIRKI_WORDPRESS_SORT_BY_OPTIONS',
		array( 'none', 'ID', 'author', 'title', 'name', 'type', 'date', 'modified', 'parent', 'comment_count', 'menu_order' )
	);
}

if (!defined('KIRKI_PRESERVED_CLASS_LIST')) {
	define(
		'KIRKI_PRESERVED_CLASS_LIST',
		array(
			'kirki-current-tab',
			'kirki-tab-active',
			'kirki-slide-active',
			'kirki-slider-nav-item-active',
			'kirki-slider-arrow-left-active',
			'kirki-slider-arrow-right-active',
			'kirki-navigation-item-active',
			'kirki-active',
			'kirki-pagination-item-active',
			'kirki-active-link',
		)
	);
}

if (!defined('KIRKI_PLUGIN_SETTINGS')) {
	define(
		'KIRKI_PLUGIN_SETTINGS',
		array(
			'INPUT_TEXT'                     => array(
				'type'        => 'inputtext',
				'placeholder' => 'Placeholder text',
			),
			'INPUT'                          => array(
				'type'        => 'input',
				'placeholder' => 'Placeholder text',
			),
			'INPUT_NUMBER'                   => array(
				'type'        => 'inputnumber',
				'placeholder' => 'Placeholder text',
			),
			'DYNAMIC_INPUT'                  => array(
				'type'        => 'dynamicinput',
				'placeholder' => 'Placeholder text',
			),
			'CHECKBOX'                       => array( 'type' => 'checkbox' ),
			'TOGGLER'                        => array( 'type' => 'toggler' ),
			'COLOR_PICKER'                   => array(
				'type'  => 'colorpicker',
				'title' => 'Heading Color',
			),
			'SELECT'                         => array(
				'type'    => 'select',
				'options' => array(
					array(
						'value' => 'value1',
						'title' => 'Value One',
					),
					array(
						'value' => 'value2',
						'title' => 'Value Two',
					),
				),
			),
			'DIVIDER_FULL'                   => array(
				'type'  => 'divider',
				'style' => 'full',
			),
			'DIVIDER_HALF'                   => array(
				'type'  => 'divider',
				'style' => 'half',
			),
			'DIVIDER_TRANSPARENT'            => array( 'type' => 'divider_tansparent' ),
			'TAB'                            => array(
				'type' => 'tab',
				'tabs' => array(),
			),
			'DATEPICKER'                     => array( 'type' => 'datepicker' ),
			'SELECT_WITH_WP_POST_SUGGESTION' => array(
				'postType' =>'kirki_popup',
				'type'     => 'select_with_wp_post_suggestion',
			),
		)
	);
}
