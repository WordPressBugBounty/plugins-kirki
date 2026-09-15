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

		if ( isset( $data['google_font_api_key'] ) ) {
			$new_data['google_font_api_key'] = $data['google_font_api_key'];
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

		// Pusher credentials: sanitize, validate, store.
		if ( isset( $data['pusher_credentials'] ) ) {
			$creds = $data['pusher_credentials'];

			// Removal: empty credentials object clears stored values.
			if ( empty( $creds['app_id'] ) && empty( $creds['app_key'] ) && empty( $creds['app_secret'] ) ) {
				$new_data['pusher_credentials']  = array();
				$new_data['broadcasting_driver'] = 'eventstream';
			} else {
				// Sanitize every value.
				$sanitized = array(
					'app_id'     => sanitize_text_field( $creds['app_id'] ?? '' ),
					'app_key'    => sanitize_text_field( $creds['app_key'] ?? '' ),
					'cluster'    => sanitize_text_field( $creds['cluster'] ?? 'mt1' ),
					'app_secret' => sanitize_text_field( $creds['app_secret'] ?? '' ),
				);

				// All four fields are required.
				if ( empty( $sanitized['app_id'] ) || empty( $sanitized['app_key'] ) || empty( $sanitized['cluster'] ) || empty( $sanitized['app_secret'] ) ) {
					$new_data['pusher_credentials']  = array();
					$new_data['broadcasting_driver'] = 'eventstream';
				} else {
					$existing_creds = $new_data['pusher_credentials'] ?? array();
					$is_unchanged   = (
						isset( $existing_creds['app_id'], $existing_creds['app_key'], $existing_creds['cluster'], $existing_creds['app_secret'] ) &&
						$existing_creds['app_id'] === $sanitized['app_id'] &&
						$existing_creds['app_key'] === $sanitized['app_key'] &&
						$existing_creds['cluster'] === $sanitized['cluster'] &&
						$existing_creds['app_secret'] === $sanitized['app_secret']
					);

					if ( $is_unchanged ) {
						$new_data['pusher_credentials'] = $sanitized;
					} else {
						// Validate by connecting to Pusher.
						$valid = self::validate_pusher_credentials( $sanitized );

						if ( $valid ) {
							$new_data['pusher_credentials']  = $sanitized;
							$new_data['broadcasting_driver'] = 'pusher';
						} else {
							// Invalid credentials — don't store, stay on eventstream.
							$new_data['pusher_credentials']  = array();
							$new_data['broadcasting_driver'] = 'eventstream';
						}
					}
				}
			}
		}

		if ( isset( $data['broadcasting_driver'] ) ) {
			$driver = sanitize_text_field( $data['broadcasting_driver'] );
			if ( 'pusher' === $driver ) {
				$pusher    = $new_data['pusher_credentials'] ?? array();
				$has_creds = ! empty( $pusher['app_id'] ) && ! empty( $pusher['app_key'] ) && ! empty( $pusher['cluster'] ) && ! empty( $pusher['app_secret'] );
				$new_data['broadcasting_driver'] = $has_creds ? 'pusher' : 'eventstream';
			} else {
				$new_data['broadcasting_driver'] = 'eventstream';
			}
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
	 * Validate Pusher credentials by making a test API call.
	 *
	 * @param array $creds Sanitized credentials (app_id, app_key, cluster, app_secret).
	 *
	 * @return bool True when the credentials are valid.
	 */
	private static function validate_pusher_credentials( $creds ) {
		try {
			$pusher = new \Pusher\Pusher(
				$creds['app_key'],
				$creds['app_secret'],
				$creds['app_id'],
				array(
					'cluster' => $creds['cluster'],
					'useTLS'  => true,
				)
			);

			// GET /channels is the lightest read-only API call.
			$result = $pusher->getChannels();

			// A successful response is an object; a failure throws or returns false.
			return false !== $result;
		} catch ( \Exception $e ) {
			return false;
		}
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
		if ( ! isset( $data['broadcasting_driver'] ) ) {
			$pusher                      = $data['pusher_credentials'] ?? array();
			$has_creds                   = ! empty( $pusher['app_id'] ) && ! empty( $pusher['app_key'] ) && ! empty( $pusher['cluster'] ) && ! empty( $pusher['app_secret'] );
			$data['broadcasting_driver'] = $has_creds ? 'pusher' : 'eventstream';
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
