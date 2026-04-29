<?php
/**
 * WpAdmin dashboard api calls
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use Kirki\HelperFunctions;


/**
 * WpAdmin API Class
 */
class WpAdmin {

	/**
	 * Save common data from dashboard
	 *
	 * @return void wp_send_json.
	 */
	public static function save_common_data() {
    //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = isset( $_POST['data'] ) ? $_POST['data'] : null;
		$data = json_decode( stripslashes( $data ), true );

		$new_data = self::get_common_data( true );

		if ( isset( $data['license_key'], $data['license_key']['key'] ) ) {
			if ( $data['license_key']['key'] !== '' ) {
				$license_key             = $data['license_key']['key'];
				$license_info            = HelperFunctions::get_my_license_info( $license_key );
				$new_data['license_key'] = $license_info;
			} else {
				$new_data['license_key'] = array(
					'key'   => '',
					'valid' => false,
				);
			}
		}

		if ( isset( $data['json_upload'] ) ) {
			$new_data['json_upload'] = $data['json_upload'];
		}
		if ( isset( $data['pexels_api_key'] ) ) {
			$new_data['pexels_api_key'] = $data['pexels_api_key'];
		}
		if ( isset( $data['pexels_status'] ) ) {
			$new_data['pexels_status'] = $data['pexels_status'];
		}
		if ( isset( $data['svg_upload'] ) ) {
			$new_data['svg_upload'] = $data['svg_upload'];
		}
		if ( isset( $data['is_show_wp_theme_header_footer'] ) ) {
			$new_data['is_show_wp_theme_header_footer'] = $data['is_show_wp_theme_header_footer'];
		}
		if ( isset( $data['image_optimization'] ) ) {
			$new_data['image_optimization'] = $data['image_optimization'];
		}

		if ( isset( $data['unsplash_api_key'] ) ) {
			$new_data['unsplash_api_key'] = $data['unsplash_api_key'];
		}
		if ( isset( $data['unsplash_status'] ) ) {
			$new_data['unsplash_status'] = $data['unsplash_status'];
		}

		if ( isset( $data['reCAPTCHA_status'] ) ) {
			$new_data['reCAPTCHA_status'] = $data['reCAPTCHA_status'];
		}

		if ( isset( $data['smooth_scroll'] ) ) {
			$new_data['smooth_scroll'] = $data['smooth_scroll'];
		}

		if ( isset( $data['recaptcha'] ) ) {
			// set data version wise, e.g:     { GRC_version: '2.0', '2.0:{}, '3.0:{} }.
			$new_data['recaptcha']['GRC_version']                           = $data['recaptcha']['GRC_version'];
			$new_data['recaptcha'][ $new_data['recaptcha']['GRC_version'] ] = $data['recaptcha'][ $data['recaptcha']['GRC_version'] ];
		}

		if ( isset( $data['chatGPT_api_key'] ) ) {
			$new_data['chatGPT_api_key'] = $data['chatGPT_api_key'];
		}
		if ( isset( $data['chatGPT_status'] ) ) {
			$new_data['chatGPT_status'] = $data['chatGPT_status'];
		}

		update_option( KIRKI_WP_ADMIN_COMMON_DATA, $new_data, false );

		wp_send_json(
			array(
				'status' => 'success',
				'data'   => self::get_common_data( true ),
			)
		);
	}

	/**
	 * Get common data
	 *
	 * @param boolean $inernal if this method call from intenally.
	 * @return void|array wp_send_json.
	 */
	public static function get_common_data( $inernal = false ) {
		$data                        = get_option( KIRKI_WP_ADMIN_COMMON_DATA, array() );
		$data['post_max_size']       = ini_get( 'post_max_size' );
		$data['php_zip_ext_enabled'] = class_exists( 'ZipArchive' );
		if ( ! isset( $data['is_show_wp_theme_header_footer'] ) ) {
			$data['is_show_wp_theme_header_footer'] = true;
		}
		if ( $inernal ) {
			return $data;
		} else {
			if ( HelperFunctions::is_api_call_from_editor_preview() ) {
				wp_send_json( array( 'license_key' => $data['license_key'] ) );
			}
			wp_send_json( $data );
		}
	}

	/**
	 * Update common data license and editor type data.
	 *
	 * @return void wp_send_json_success.
	 */
	public static function update_license_validity() {
		$valid = filter_input( INPUT_POST, 'valid', FILTER_VALIDATE_BOOLEAN );
		$data  = self::get_common_data( true );

		if ( isset( $data['license_key'] ) ) {
			$data['license_key']['valid'] = $valid;

			update_option( KIRKI_WP_ADMIN_COMMON_DATA, $data, false );
			wp_send_json_success( true );
		}
	}
}
