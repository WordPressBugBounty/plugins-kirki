<?php
/**
 * Iframe class for controlling editor and iframe related customize post content.
 *
 * @package kirki
 */

namespace Kirki\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\Frontend\Preview\Preview;
use Kirki\HelperFunctions;
use Kirki\Staging;

/**
 * Iframe class
 */
class Iframe {


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct( $staging_version = false ) {
		$this->render_editor();

		if ( ! $staging_version ) {
			$staging_version = Staging::get_most_recent_stage_version( HelperFunctions::get_post_id_if_possible_from_url(), false );
		}
		new TheFrontend( 'iframe', $staging_version );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'init', array( $this, 'disable_wp_emojicons' ) );
		add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
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
	 * Render the editor
	 *
	 * @return void
	 */
	public function render_editor() {
		if ( ! $this->has_editor_access() ) {
      //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.I18n.NonSingularStringLiteralDomain
			wp_die( __( 'Sorry you are not allowed to access this page', 'kirki' ), 403 );
		}
		HelperFunctions::remove_wp_assets();
	}


	/**
	 * Load assets for Editor
	 *
	 * @return void
	 */
	public function load_assets() {
		$version = KIRKI_VERSION;
		wp_enqueue_style('kirki-iframe', KIRKI_ASSETS_URL . 'css/kirki-iframe.min.css', null, $version );
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
