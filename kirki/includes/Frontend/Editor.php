<?php
/**
 * Editor controller
 *
 * @package kirki
 */

namespace Kirki\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\Ajax\WpAdmin;
use Kirki\HelperFunctions;
use Kirki\Staging;

/**
 * Editor class for controlling editor page template and hooks
 */
class Editor {


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		$this->render_editor();
	}

	/**
	 * Render the editor page
	 *
	 * @return void
	 */
	public function render_editor() {
		if ( ! $this->has_editor_access() ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.I18n.NonSingularStringLiteralDomain
			wp_die( __( 'Sorry you are not allowed to access this page', 'kirki' ), 403 );
		}
		add_filter( 'template_include', array( $this, 'load_my_editor_view' ), PHP_INT_MAX );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_for_iframe' ), PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_script_text_domain' ), 1 );

		// add_action( 'wp_enqueue_scripts', array( new HelperFunctions(), 'remove_theme_style' ), 100 );
		add_action( 'wp_enqueue_scripts', array( new HelperFunctions(), 'dequeue_all_except_my_plugin' ), 100 ); // Low priority to run late

		add_action( 'wp_footer', array( $this, 'add_before_body_tag_end' ) );

		add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
		add_action( 'init', array( $this, 'disable_wp_emojicons' ) );

		
	}

	public function disable_wp_emojicons() {
		// Remove emoji script and styles from front-end and admin
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );

		// Remove from RSS feeds
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// Remove from emails
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// Remove TinyMCE emoji plugin
		add_filter(
			'tiny_mce_plugins',
			function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
			}
		);

		// Disable DNS prefetch
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	public function remove_jquery_migrate( $scripts ) {
		if ( isset( $scripts->registered['jquery'] ) ) {
			$script = $scripts->registered['jquery'];
			if ( $script->deps ) {
				$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
			}
		}
	}

	/**
	 * Load script text domain
	 *
	 * @return void
	 */
	public function load_script_text_domain() {
		HelperFunctions::load_script_text_domain('kirki-editor' );
	}

	/**
	 * Render Editor View from kirki custom page template.
	 *
	 * @param string $template template name.
	 * @return string template location.
	 */
	public function load_my_editor_view( $template ) {
		$template = dirname( __FILE__ ) . '/views/editor.php';
		return $template;
	}

	public function enqueue_scripts_for_iframe() {
		global $wp_scripts, $wp_styles;

		$scripts = [];
		foreach ($wp_scripts->registered as $handle => $script) {
			if (!empty($script->src) && strpos($script->src, '/themes/') !== false) {
				$scripts[] = $script->src;
			}
		}

		$styles = [];
		foreach ($wp_styles->registered as $handle => $style) {
			if (!empty($style->src) && strpos($style->src, '/themes/') !== false) {
				$styles[] = $style->src;
			}
		}


		wp_localize_script(
			'kirki-editor',
			'wp_assets',
			[
				'scripts' => $scripts,
				'styles'  => $styles,
			]
		);
	}

	/**
	 * Load Assets
	 *
	 * @return void
	 */
	public function load_assets() {
		HelperFunctions::remove_wp_assets();
		$version = KIRKI_VERSION;

		$screen = HelperFunctions::sanitize_text( isset( $_GET['screen'] ) ? $_GET['screen'] : '' );
		$is_current_screen_dashboard = $screen === 'dashboard' ? true : false;

    wp_enqueue_media();

		wp_enqueue_script('kirki-editor', KIRKI_ASSETS_URL . 'js/kirki-editor.min.js', array( 'wp-i18n' ), $version, true );
		wp_enqueue_script( 'kirki', KIRKI_ASSETS_URL . 'js/kirki.min.js', array( 'wp-i18n' ), $version, true );

		$post_id = HelperFunctions::get_post_id_if_possible_from_url();

		if($is_current_screen_dashboard && !$post_id){
			// get first post id from current post type
			$post_id = get_posts(
				array(
					'post_type' => 'any',
					'numberposts' => 1,
					'fields' => 'ids',
				)
			);
			$post_id = $post_id[0];
		}
		
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$post_type = $post->post_type;

		$staging_version = isset( $_GET['staging_version'] ) ? intval( HelperFunctions::sanitize_text( $_GET['staging_version'] ) ) : false;
		if ( ! $staging_version ) {
			$staging_version = Staging::get_most_recent_stage_version( $post_id, false );
		}

		$post_url_arr = HelperFunctions::get_post_url_arr_from_post_id(
			$post_id,
			array(
				'ajax_url'             => true,
				'rest_url'             => true,
				'nonce'                => true,
				'site_url'             => true,
				'admin_url'            => true,
				'post_id'              => true,
				'core_plugin_url'      => true,
				'preview_url'          => true,
				'editor_preview_token' => true,
			)
		);

		$staging_nonce = false;
		if ( $staging_version ) {
			$staging_nonce = wp_create_nonce( 'kirki_preview_staging_nonce' );
		}

		$wp_kirki = array(
			'ajaxUrl'                 => $post_url_arr['ajax_url'],
			'restUrl'                 => $post_url_arr['rest_url'],
			'nonce'                   => $post_url_arr['nonce'],
			'siteUrl'                 => $post_url_arr['site_url'],
			'adminUrl'                => $post_url_arr['admin_url'],
			'postId'                  => $post_url_arr['post_id'],
			'corePluginUrl'           => $post_url_arr['core_plugin_url'],
			'postPreviewUrl'          => $post_url_arr['preview_url'],
			'editor_preview_token'    => $post_url_arr['editor_preview_token'],
			'uploadBaseUrl'           => wp_upload_dir()['baseurl'],
			'postTitle'               => get_the_title( $post_id ),
			'postType'                => $post_type,
			'kirkiWPDashboard'        => $post_url_arr['site_url'] . '/wp-admin',
			'postWpEditUrl'           => get_edit_post_link( $post_id ),
			'version'                 => KIRKI_VERSION,
			'default_menu_id'         => $this->get_defalut_menu_id(), // TODO: need to check this menu related code
			'current_user_id'         => get_current_user_id(),
			'current_user_avatar_url' => get_avatar_url( get_current_user_id() ),
			'current_user_name'       => get_the_author_meta( 'display_name', get_current_user_id() ),
			'staging_version'         => $staging_version,
			'staging_nonce'           => $staging_nonce,
			'current_screen' => $is_current_screen_dashboard ? 'dashboard' : 'editor',
			'assetsUrlBase'           => KIRKI_ASSETS_URL,
		);
		wp_localize_script('kirki-editor', 'wp_kirki', $wp_kirki );
		wp_enqueue_style('kirki-kirki', KIRKI_ASSETS_URL . 'css/kirki.min.css', null, $version );
		wp_enqueue_style('kirki-editor', KIRKI_ASSETS_URL . 'css/kirki-editor.min.css', null, $version );

		// Store the handles of your plugin's scripts and styles
		$editor_assets = array(
			'scripts' => array( 'kirki','kirki-editor' ), // here we add kirki.min.js 'kirki' too. cause some theme prevent this script from loading.
			'styles'  => array('kirki-kirki','kirki-editor' ),
		);

		if ( defined( 'TDE_APP_PREFIX' ) ) {
			// tutor support
			$editor_assets['scripts'][] = TDE_APP_PREFIX . '-tutor-kirki-elements';
			$editor_assets['styles'][]  = TDE_APP_PREFIX . '-tutor-kirki-elements';
		}

		// Make the handles accessible globally
		global $kirki_editor_assets;
		$kirki_editor_assets = $editor_assets;
	}

	public function add_before_body_tag_end() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$s  = '';
		$s .= HelperFunctions::get_view_port_lists();
		// $s contains a pre-rendered <script> tag from HelperFunctions::get_view_port_lists().
		echo $s; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	private function get_defalut_menu_id() {
		$menus           = wp_get_nav_menus();
		$first_menu_id   = count( $menus ) > 0 ? $menus[0]->term_id : 0;
		$nav_locations   = get_nav_menu_locations();
		$primary_menu_id = $nav_locations && isset( $nav_locations['primary'] ) ? $nav_locations['primary'] : 0;
		$default_menu_id = $primary_menu_id == 0 ? $first_menu_id : $primary_menu_id;
		return $default_menu_id;
	}

	/**
	 * Check the editor access
	 *
	 * @return boolean
	 */
	public function has_editor_access() {
		return HelperFunctions::user_has_editor_access();
	}
}
