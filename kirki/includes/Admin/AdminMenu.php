<?php
/**
 * AdminMenu for wp admin menu and icon management
 *
 * @package kirki
 */

namespace Kirki\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use Kirki\HelperFunctions;


/**
 * AdminMenu Class
 */
class AdminMenu {

	/**
	 * Dashboard submenu configuration keyed by menu slug.
	 *
	 * @var array<string, array{title: string, toolbar: string}>
	 */
	private $dashboard_toolbar_submenus = array(
		'kirki-home'  => array(
			'title'   => 'Canvas',
			'toolbar' => 'home',
		),
		'kirki-submissions'  => array(
			'title'   => 'Submissions',
			'toolbar' => 'submissions',
		),
		'kirki-role-manager' => array(
			'title'   => 'Role Managers',
			'toolbar' => 'role-manager',
		),
		'kirki-settings'     => array(
			'title'   => 'Settings',
			'toolbar' => 'settings',
		),
		
	);


	/**
	 * Initilize the class
	 *
	 * @return void
	 */
	public function __construct() {
		if ( HelperFunctions::user_is( 'administrator' ) ) {
			\add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			\add_action( 'admin_enqueue_scripts', array( $this, 'load_script_text_domain' ), 100 );
			\add_action( 'admin_init', array( $this, 'maybe_redirect_kirki_menu' ) );
		}
		\add_action( 'admin_head', array( $this, 'add_kirki_admin_styles' ) );
	}

	/**
	 * Kirki Logo for kirki menu
	 *
	 * @return void
	 */
	public static function add_kirki_admin_styles() {
		echo \wp_kses(
			' <style> .dashicons-kirki { background-image: url("' . KIRKI_ASSETS_URL . 'images/kirki-20X20.svg"); background-repeat: no-repeat; background-position: center;background-size: 20px 20px; }
		[href="admin.php?page=kirki-get-pro"] {
			background: linear-gradient(93.07deg, rgba(78, 94, 218, 0.7) -4.71%, rgba(253, 98, 96, 0.7) 107.25%) !important;
			color: #fff !important;
			font-weight: bold !important;
			width: 125px;
		}
			#toplevel_page_kirki .wp-first-item{
				display: none;
			}
				
		#toplevel_page_kirki:hover .dashicons-kirki {
			background-image: url("' . KIRKI_ASSETS_URL . 'images/kirki-hovered-20X20.svg") !important;
		}
			</style> ',
			array( 'style' => array() )
		);
	}


	/**
	 * Remove custom menu from sidebar
	 *
	 * @return void
	 */
	public function remove_custom_menu_from_sidebar() {
		\remove_menu_page( 'edit.php?post_type=kirki_page' );
	}

	/**
	 * Load scritp text domain
	 *
	 * @return void
	 */
	public function load_script_text_domain() {
		HelperFunctions::load_script_text_domain( 'kirki-admin' );
	}


	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		\add_menu_page( 'Kirki - Home', 'Kirki', 'edit_posts', 'kirki', array( $this, 'plugin_page' ), 'dashicons-kirki', 25 );

		foreach ( $this->dashboard_toolbar_submenus as $slug => $submenu ) {
			\add_submenu_page(
				'kirki',
				$submenu['title'],
				$submenu['title'],
				'manage_options',
				$slug,
				array( $this, 'plugin_page' )
			);
		}
	}

	/**
	 * Render the menu page
	 *
	 * @return void
	 */
	public function plugin_page() {
		include_once __DIR__ . '/views/dashboard.php';
	}

	/**
	 * Redirect Kirki top-level menu to frontend dashboard before headers are sent
	 */
	public function maybe_redirect_kirki_menu() {
		if ( empty( $_GET['page'] ) ) {
			return;
		}

		$page_slug = \sanitize_key( \wp_unslash( $_GET['page'] ) );

		if ( 'kirki' === $page_slug ) {
			$dashboard_url = \home_url( '/?action=kirki&screen=dashboard&toolbar=home' );
			\wp_safe_redirect( $dashboard_url );
			exit;
		}

		if ( isset( $this->dashboard_toolbar_submenus[ $page_slug ] ) ) {
			$toolbar       = $this->dashboard_toolbar_submenus[ $page_slug ]['toolbar'];
			$dashboard_url = \home_url( '/?action=kirki&screen=dashboard&toolbar=' . rawurlencode( $toolbar ) );
			\wp_safe_redirect( $dashboard_url );
			exit;
		}
	}

	/**
	 * Get pro page
	 *
	 * @return void
	 */
	public function kirki_get_pro_page() {
		include_once __DIR__ . '/views/get-pro.php';
	}
}
