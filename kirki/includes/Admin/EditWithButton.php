<?php
/**
 * EditWithButton show on post or custom post type edit page in wp admin page
 *
 * @package kirki
 */

namespace Kirki\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;
use Kirki\Admin\AdminMenu;


/**
 * EditWithButton Class
 */
class EditWithButton {


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		if ( HelperFunctions::user_has_editor_access() ) {
			add_filter( 'post_row_actions', array( $this, 'filter_post_row_actions' ), 10, 2 );
			add_filter( 'page_row_actions', array( $this, 'filter_post_row_actions' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_custom_button_click_functionality' ), 10, 1 );
			add_action( 'admin_bar_menu', array( $this, 'add_edit_with_kirki_button' ), 100 );
		}
	}

	public function add_edit_with_kirki_button( $wp_admin_bar ) {
		global $post;

		// Ensure we are on the front end and there's a valid post
		if ( is_admin_bar_showing() && is_singular() && isset( $post->ID ) ) {

			// Check if the editor mode is set to "Kirki" and if the user has edit access
			$editor_mode = HelperFunctions::is_editor_mode_is_kirki( $post->ID );
			if ( $editor_mode && HelperFunctions::user_has_post_edit_access( $post->ID ) ) {

				// Fetch the post URLs using your existing helper functions
				$post_url_arr = HelperFunctions::get_post_url_arr_from_post_id(
					$post->ID,
					array(
						'editor_url' => true,
						'ajax_url'   => true,
					)
				);

				// Add the custom button to the admin bar
				$wp_admin_bar->add_node(
					array(
						'id'                  => 'edit_with_kirki', // Unique ID for the button
						'title'               => '<span class="dashicons-kirki" style="padding: 10px;"></span>&nbsp ' . esc_html__( 'Edit with Kirki', 'kirki' ), // Button content
						'href'                => $post_url_arr['editor_url'], // URL to the XYZ editor
						'ajaxUrl'             => $post_url_arr['ajax_url'],
						'action'              => KIRKI_EDITOR_ACTION,
						'isEditorModeIsKirki' => $editor_mode,
						'postId'              => get_the_ID(),
						'nonce'               => wp_create_nonce( 'wp_rest' ),
					)
				);
			}
		}
		AdminMenu::add_kirki_admin_styles();
	}



	/**
	 * Filter Post Row Actions.
	 *
	 * Let the Document to filter the array of row action links on the Posts list table.
	 *
	 * @param array    $actions wp actions.
	 * @param \WP_Post $post wp post.
	 *
	 * @return array
	 */
	public function filter_post_row_actions( $actions, $post ) {
		$editor_mode = HelperFunctions::is_editor_mode_is_kirki( $post->ID );
		if ( $editor_mode && HelperFunctions::user_has_post_edit_access( $post->ID ) ) {
			$editor_url                                 = HelperFunctions::get_post_url_arr_from_post_id( $post->ID, array( 'editor_url' => true ) )['editor_url'];
			$actions[ 'edit_with_' . 'kirki' ] = sprintf(
				'<a href="%1$s" style="color:#9353FF;">%2$s</a>',
				$editor_url,
				esc_html__( 'Edit with Kirki', 'kirki' )
			);
		}
		return $actions;
	}

	/**
	 * Add custom link in toolbar and click functionality
	 *
	 * @param string $hook wp hook or page name.
	 *
	 * @return void
	 */
	public function add_custom_button_click_functionality( $hook ) {
		global $post;

		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			$version      = KIRKI_VERSION;
			$editor_mode  = HelperFunctions::is_editor_mode_is_kirki( get_the_ID() );
			$post_url_arr = HelperFunctions::get_post_url_arr_from_post_id(
				get_the_ID(),
				array(
					'editor_url' => true,
					'ajax_url'   => true,
				)
			);
			wp_enqueue_script( 'custom_link', KIRKI_ASSETS_URL . 'js/admin/custom-link-in-toolbar.js', array(), $version, true );
			wp_localize_script(
				'custom_link',
				'kirki_admin',
				array(
					'postEditURL'         => $post_url_arr['editor_url'],
					'ajaxUrl'             => $post_url_arr['ajax_url'],
					'action'              => KIRKI_EDITOR_ACTION,
					'isEditorModeIsKirki' => $editor_mode,
					'postId'              => get_the_ID(),
					'nonce'               => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}
}
