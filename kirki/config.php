<?php
/**
 * Kirki Configurations
 *
 * @package kirki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'KIRKI_VERSION', '6.0.2' );
define( 'KIRKI_APP_PREFIX', 'kirki' );
define( 'KIRKI_CORE_PLUGIN_URL', 'https://kirki.com' );
define( 'KIRKI_PUBLIC_ASSETS_URL', 'https://d31d7414w5c76z.cloudfront.net' );

define( 'KIRKI_META_NAME_FOR_POST_EDITOR_MODE', 'kirki_editor_mode' );
define( 'KIRKI_META_NAME_FOR_USED_STYLE_BLOCK_IDS', 'kirki_used_style_block_ids' );
define( 'KIRKI_META_NAME_FOR_USED_FONT_LIST', 'kirki_used_font_list' );
define( 'KIRKI_META_NAME_FOR_STAGED_VERSIONS', 'kirki_stage_versions' );
define( 'KIRKI_META_NAME_FOR_PAGE_HF_SYMBOL_DISABLE_STATUS', 'kirki_disabled_page_symbols' );


// droip apps.
define( 'IS_DEVELOPING_KIRKI_APPS', false );
if ( IS_DEVELOPING_KIRKI_APPS ) {
	define( 'KIRKI_APPS_BASE_URL', content_url() . '/kirki-apps-dev/build' );
} else {
	define( 'KIRKI_APPS_BASE_URL', KIRKI_PUBLIC_ASSETS_URL . '/kirki-apps' );
}
define( 'KIRKI_DEVELOPING_APPS_INCLUDES', plugin_dir_path( __FILE__ ) . '../../kirki-apps-dev/index.php' );


/* all types of file paths start */
define( 'KIRKI_PLUGIN_REL_URL', dirname( plugin_basename( __FILE__ ) ) );
define( 'KIRKI_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'KIRKI_ASSETS_URL', KIRKI_ROOT_URL . 'assets/' );
define( 'KIRKI_ROOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'KIRKI_FULL_CANVAS_TEMPLATE_PATH', str_replace( '\\', '/', KIRKI_ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'page.php' ) );
/* all types of file paths end */

/* all types of post types start */
define( 'KIRKI_GLOBAL_DATA_POST_TYPE_NAME','kirki_global_data' );
define( 'KIRKI_POST_TYPE','kirki_post' );
define( 'KIRKI_SYMBOL_TYPE','kirki_symbol' );
define( 'KIRKI_CONTENT_MANAGER_PREFIX','kirki_cm' );
/* all types of post types end */

/* all types of option meta types start */
define( 'KIRKI_USER_CONTROLLER_META_KEY','kirki_user_controller' );
define( 'KIRKI_GLOBAL_STYLE_BLOCK_META_KEY','kirki_global_style_block' );
define( 'KIRKI_USER_SAVED_DATA_META_KEY','kirki_user_saved_data' );
define( 'KIRKI_USER_CUSTOM_FONTS_META_KEY','kirki_user_custom_fonts' );
define( 'KIRKI_PAGE_SEO_SETTINGS_META_KEY','kirki_page_seo_settings' );
define( 'KIRKI_PAGE_CUSTOM_CODE','kirki_page_custom_code' );

define( 'KIRKI_WP_ADMIN_COMMON_DATA','kirki_wp_admin_common_data' );
/* all types of option meta types end */

/* all types of user meta types start */
define( 'KIRKI_USER_WALKTHROUGH_SHOWN_META_KEY','kirki_user_walkthrough_shown_state' );
/* all types of user meta types end */

define( 'KIRKI_EDITOR_ACTION', 'kirki' );

define(
	'KIRKI_ACCESS_LEVELS',
	array(
		'NO_ACCESS'      => 'no',
		'FULL_ACCESS'    => 'full',
		'CONTENT_ACCESS' => 'content',
		'VIEW_ACCESS'    => 'view',
	)
);
define( 'KIRKI_USERS_DEFAULT_FULL_ACCESS', array( 'administrator', 'editor' ) );

/* Custom DB table names */
define( 'KIRKI_FORM_TABLE','kirki_forms' );
define( 'KIRKI_FORM_DATA_TABLE','kirki_forms_data' );
define( 'KIRKI_COLLABORATION_TABLE','kirki_collaborations' );
define( 'KIRKI_CM_REFERENCE_TABLE','kirki_cm_reference' );
define( 'KIRKI_COMMENTS_TABLE','kirki_comments' );
/* Custom DB table names */

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


define(
	'KIRKI_SUPPORTED_MEDIA_TYPES_FOR_FILE_INPUT',
	array(
		'default'                 => array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/ogg', 'video/quicktime', 'application/pdf', 'application/msword', 'text/plain' ),
		'.doc, .pdf, .txt'        => array( 'application/pdf', 'application/msword', 'text/plain' ),
		'.mp4, .mov'              => array( 'video/mp4', 'video/ogg', 'video/quicktime' ),
		'.jpg, .jpeg, .png, .gif' => array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ),
	)
);

define(
	'KIRKI_WORDPRESS_SORT_BY_OPTIONS',
	array( 'none', 'ID', 'author', 'title', 'name', 'type', 'date', 'modified', 'parent', 'comment_count', 'menu_order' )
);

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

define(
	'KIRKI_MIGRATION_REPLACEMENTS',
	array(
		'droip_global_data'           => 'kirki_global_data',
		'droip_global_style_block'    => 'kirki_global_style_block',
		'droip_template_conditions'   => 'kirki_template_conditions',
		'droip_disabled_page_symbols' => 'kirki_disabled_page_symbols',
		'droip_used_style_block_ids'  => 'kirki_used_style_block_ids',
		'droip_imported_batch_id'     => 'kirki_imported_batch_id',
		'droip_page_seo_settings'     => 'kirki_page_seo_settings',
		'droip_user_custom_fonts'     => 'kirki_user_custom_fonts',
		'droip_page_custom_code'      => 'kirki_page_custom_code',
		'droip_stage_versions'        => 'kirki_stage_versions',
		'droip_used_font_list'        => 'kirki_used_font_list',
		'droip_user_controller'       => 'kirki_user_controller',
		'droip_user_saved_data'       => 'kirki_user_saved_data',
		'droip_editor_mode'           => 'kirki_editor_mode',
		'droip_cm_fields'             => 'kirki_cm_fields',
		'droip_cm_field_'             => 'kirki_cm_field_',

		'droip_template'              => 'kirki_template',
		'droip_utility'               => 'kirki_utility',
		'droip_symbol'                => 'kirki_symbol',
		'droip_popup'                 => 'kirki_popup',
		'droip_post'                  => 'kirki_post',
		'droip_cm'                    => 'kirki_cm',

		'DROIP_'                      => 'KIRKI_',
		'Droip_'                      => 'Kirki_',
		'droip_'                      => 'kirki_',
		'droip-'                      => 'kirki-',
		'_droip'                      => '_kirki',
		'-droip'                      => '-kirki',

		'"droip_'                     => '"kirki_',
		'"droip"'                     => '"kirki"',
		"'droip_"                     => "'kirki_",
		"'droip'"                     => "'kirki'",

		'droip'                       => 'kirki',
		'Droip'                       => 'Kirki',
		'DROIP'                       => 'KIRKI',
	)
);