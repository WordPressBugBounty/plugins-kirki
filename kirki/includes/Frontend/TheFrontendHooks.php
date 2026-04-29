<?php
/**
 * Front end post hooks and content controller
 *
 * @package kirki
 */

namespace Kirki\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;

/**
 * TheFrontendHooks class
 */
class TheFrontendHooks {

	/**
	 * Flag for where from call this instance
	 */
	private $call_from = 'front-end';

	public function __construct( $call_from ) {
		$this->call_from = $call_from;
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'wp_body_open', array( $this, 'load_custom_header' ), 1, 1 ); // call if template has get_header method.
		add_action( 'wp_footer', array( $this, 'load_custom_footer' ), 1, 1 );    // call if template has get_header method.
		add_action( 'wp_footer', array( $this, 'add_before_body_tag_end' ) );
		add_action( 'template_redirect', array( $this, 'may_be_change_header_footer' ) );
	}

	public function may_be_change_header_footer() {
		ob_start( array( $this, 'check_and_change_header_and_footer' ) );
	}
	public function check_and_change_header_and_footer( $html ) {
		global $kirki_custom_header, $kirki_custom_footer;
		if ( $kirki_custom_header ) {
			$html = preg_replace(
				'#<header(?![^>]*\sdata-kirki=["\']).*?>.*?</header>#is',
				'
<!-- kirki-custom-header will
  be loaded -->',
				$html
			);
		}
		if ( $kirki_custom_footer ) {
			$html = preg_replace(
				'#<footer(?![^>]*\sdata-kirki=["\']).*?>.*?</footer>#is',
				'
    <!-- kirki-custom-footer will be loaded
      -->',
				$html
			);
		}
		return $html;
	}

	public function load_assets() {
		wp_enqueue_script( 'kirki', KIRKI_ASSETS_URL . 'js/kirki.min.js', array( 'wp-i18n' ), KIRKI_VERSION, true );
		wp_enqueue_style( 'kirki', KIRKI_ASSETS_URL . 'css/kirki.min.css', null, KIRKI_VERSION );
	}

	/**
	 * Load custom header
	 *
	 * @param string $header header text.
	 * @return void
	 */
	public function load_custom_header( $header ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo HelperFunctions::get_page_custom_section( 'header' );
	}

	/**
	 * Load custom footer
	 *
	 * @param string $footer footer text.
	 * @return void
	 */
	public function load_custom_footer( $footer ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo HelperFunctions::get_page_custom_section( 'footer' );
	}

	public function add_before_body_tag_end() {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$s = '';

		$template_data = HelperFunctions::get_template_data_if_current_page_is_kirki_template();
		$context       = HelperFunctions::get_current_page_context();
		$post_id       = HelperFunctions::get_post_id_if_possible_from_url();
		$url_arr       = HelperFunctions::get_post_url_arr_from_post_id(
			$post_id,
			array(
				'ajax_url' => true,
				'rest_url' => true,
				'site_url' => true,
				'nonce'    => true,
			)
		);
		$s            .= HelperFunctions::get_view_port_lists();
		$s            .= HelperFunctions::get_kirki_css_variables_data();
		$s            .= '<script id="' .'kirki-api-and-nonce">
    window.wp_kirki = {
        ajaxUrl: "' . esc_url( $url_arr['ajax_url'] ) . '",
        restUrl: "' . esc_url( $url_arr['rest_url'] ) . '",
        siteUrl: "' . esc_url( $url_arr['site_url'] ) . '",
        apiVersion: "v1",
        postId: "' . esc_attr( $post_id ) . '",
        nonce: "' . esc_attr( $url_arr['nonce'] ) . '",
        call_from: "' . esc_attr( $this->call_from ) . '",
        templateId: "' . esc_attr( $template_data ? $template_data['template_id'] : false ) . '",
        context: ' . json_encode( $context ) . '
    };
    </script>';

		// smooth scroll
		if ( ! $this->call_from == 'iframe' ) {
			$s .= HelperFunctions::get_smooth_scroll_script();
		}

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $s;
	}

}
