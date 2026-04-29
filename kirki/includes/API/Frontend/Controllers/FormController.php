<?php

/**
 * Collection controller
 *
 * @package kirki
 */

namespace Kirki\API\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WP_REST_Server;
use Kirki\Ajax\WpAdmin;
use Kirki\FormValidator\FormValidator;
use Kirki\HelperFunctions;
use WP_Error;


/**
 * FormController for managing front end form submission.
 */
class FormController extends FrontendRESTController {


	/**
	 * Register the form routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/form',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_form_data' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * ReCaptcha token verification
	 *
	 * @param string $secret_key secret key.
	 * @param string $token token.
	 *
	 * @return bool
	 */
	private function verifyGoogleRecaptchaToken( $secret_key, $token ) {
		$url = 'https://www.google.com/recaptcha/api/siteverify';

		$response = HelperFunctions::http_post(
			$url,
			array(
				'method'      => 'POST',
				'httpversion' => '2.0',
				'headers'     => array(
					'Content-type' => 'application/x-www-form-urlencoded',
				),
				'body'        => array(
					'secret'   => $secret_key,
					'response' => $token,
				),
			),
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$response_body = json_decode( $response_body, true );

		return (bool) $response_body['success'] ?? false;
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param \WP_REST_Request $request all user request parameter.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function save_form_data( $request ) {
		$params          = $request->get_params();
		$additional_keys = array( '_kirki_form', '_wpnonce', '_wp_http_referer', 'g-recaptcha-token', 'g-recaptcha-response' );
		$form_data       = $params;

		if ( isset( $form_data['g-recaptcha-token'] ) ) {
			$common_data = WpAdmin::get_common_data( true );
			$version     = $common_data['recaptcha']['GRC_version'];
			$recaptcha   = $common_data['recaptcha'][ $version ];

			$recaptcha_secret_key = $recaptcha['GRC_secret_key'];
			$recaptcha_token      = $form_data['g-recaptcha-token'];

			$is_valid = $this->verifyGoogleRecaptchaToken( $recaptcha_secret_key, $recaptcha_token );
			if ( ! $is_valid ) {
				wp_send_json_error( 'Sorry, something error happened, please try with right data', 400 );
				exit;
			}
		}
		foreach ( $additional_keys as $key ) {
			unset( $form_data[ $key ] );
		}

		$form_meta_data_base64 = $request->get_param( '_kirki_form' );
		$wpnonce               = $request->get_param( '_wpnonce' );

		if ( ! isset( $form_meta_data_base64 ) || ! isset( $wpnonce ) ) {
			wp_send_json_error( 'Sorry, something error happened, please try with right data', 400 );
			exit;
		}

		// Retrieve form config from session.
		//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$form_meta_data = explode( '|', base64_decode( base64_decode( $form_meta_data_base64 ) ) );
		$form_id        = isset( $form_meta_data[0] ) ? $form_meta_data[0] : null;
		$post_id        = isset( $form_meta_data[1] ) ? $form_meta_data[1] : null;
		$form_config    = HelperFunctions::get_session_data( $form_id );

		if ( ! $form_config ) {
			wp_send_json_error( 'Form config not found', 400 );
			exit;
		}

		// form validation
		$validation_result = FormValidator::validate( $form_data, $form_config['fields'] );
		if ( $validation_result['has_error'] ) {
			wp_send_json_error( $validation_result, 400 );
		}

		if ( ! isset( $form_id ) || ! isset( $post_id ) || ! $form_config ) {
			wp_send_json_error( 'Sorry, something error happened, please try with right data', 400 );
			exit;
		}

		$form_name      = $form_config['name'];
		$max_entry      = $form_config['maxEntry'];
		$response_limit = $form_config['responseLimit'];
		$mail_clients   = $form_config['mailClients'];
		$actions        = $form_config['actions'];

		$email_actions      = array();
		$webhook_actions    = array();
		$mailclient_actions = array();

		$email_list = array(); // Legacy list

		if ( isset( $form_config['emailNotification'] ) ) {
			$email_notification = $form_config['emailNotification'];
			$email_list         = $email_notification['enabled'] && isset( $email_notification['emailList'] ) && is_array( $email_notification['emailList'] ) ? $email_notification['emailList'] : null;
		} else {
			foreach ( $actions as $action ) {
				if ( isset( $action['type'] ) && $action['type'] === 'email' ) {
					$email_actions[] = $action;
				} elseif ( isset( $action['type'] ) && $action['type'] === 'webhooks' ) {
					$webhook_actions[] = $action;
				} elseif ( isset( $action['type'] ) && $action['type'] === 'mailclients' ) {
					$mailclient_actions[] = $action;
				}
			}
		}

		$entry_limit     = $max_entry['restricted'] ? $max_entry['value'] : null;
		$response_limit  = $response_limit['restricted'] ? $response_limit['value'] : null;
		$mail_clients    = isset( $mail_clients ) && is_array( $mail_clients ) ? $mail_clients : null;
		$has_email_field = ( isset( $form_data['email'] ) && is_string( $form_data['email'] ) ) || ( isset( $form_data['Email'] ) && is_string( $form_data['Email'] ) );

		if ( isset( $post_id, $form_id ) ) {
			$saved_form_id = '';
			$saved_form    = $this->get_form( $post_id, $form_id );

			if ( null === $saved_form ) {
				$saved_form_id = $this->insert_form( $post_id, $form_id, $form_name );
			} else {
				$saved_form_id = $saved_form['id'];
				if ( $saved_form['name'] !== $form_name ) {
					global $wpdb;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$wpdb->prefix . KIRKI_FORM_TABLE,
						array(
							'name' => $form_name,
						),
						array( 'id' => $saved_form_id ),
						array( '%s' ),
						array( '%d' ),
					);
				}
			}

			$this->check_entry_limit( $saved_form_id, $entry_limit );
			$this->check_response_limit( $saved_form_id, $response_limit );

			if ( count( $_FILES ) > 0 ) {
				foreach ( $_FILES as $name => $fileData ) {
					if ( $fileData['error'] === UPLOAD_ERR_OK ) {

						// Sanitize file info
						$_FILES[ $name ]['name']     = wp_unslash( $fileData['name'] );
						$_FILES[ $name ]['type']     = wp_unslash( $fileData['type'] );
						$_FILES[ $name ]['tmp_name'] = wp_unslash( $fileData['tmp_name'] );
						$_FILES[ $name ]['error']    = wp_unslash( $fileData['error'] );
						$_FILES[ $name ]['size']     = wp_unslash( $fileData['size'] );

						// --- START: Custom validation ---
						// Assume $form_fields contains your form schema with 'allowed_types' and 'max_size' per field
						$field_config = $form_fields[ $name ] ?? null;
						$file_type    = $_FILES[ $name ]['type'];
						$file_size    = $_FILES[ $name ]['size'];

						if ( $field_config ) {
							// Check MIME/type allowlist
							if ( ! empty( $field_config['allowed_types'] ) && ! in_array( $file_type, $field_config['allowed_types'], true ) ) {
								$form_data[ $name ] = new WP_Error(
									'invalid_file_type',
									/* translators: %s: File type */
									sprintf( __( 'File type "%s" is not allowed for this field.', 'kirki' ), $file_type )
								);
								continue;
							}

							// Check max size
							if ( ! empty( $field_config['max_size'] ) && $file_size > $field_config['max_size'] ) {
								$form_data[ $name ] = new WP_Error(
									'file_too_large',
									/* translators: %s: Maximum file size in bytes */
									sprintf( __( 'File size exceeds the maximum allowed for this field (%s bytes).', 'kirki' ), $field_config['max_size'] )
								);
								continue;
							}
						}
						// --- END: Custom validation ---

						// Upload file safely
						$attachment_id = self::upload_file_to_media( $name );

						if ( ! is_wp_error( $attachment_id ) ) {
							$form_data[ $name ] = $attachment_id;
						} else {
							$form_data[ $name ] = $attachment_id; // Preserve WP_Error
						}
					}
				}
			}

			// Save data
			if ( isset( $saved_form_id ) && ( ! isset( $form_config['saveData'] ) || ( isset( $form_config['saveData'] ) && $form_config['saveData'] ) ) ) {
				$res = $this->insert_form_data( $form_data, $saved_form_id, $form_config['fields'] );
			} else {
				$res = true;
			}
			$res_data = false;
			if ( isset( $res ) && $res !== false ) {
				$res_data = true;
			}
		}

		// Adding shortcodes
		if ( isset( $email_actions ) && ! empty( $email_actions ) ) {
			foreach ( $email_actions as $email_action ) {
				if ( preg_match_all( '/\[([^\]]+)\]/', $email_action['emailList'], $matches ) ) {
					foreach ( $matches[1] as $match ) {
						if ( isset( $form_data[ $match ] ) ) {
							add_shortcode(
								$match,
								function() use ( $form_data, $match ) {
									return $form_data[ $match ];
								}
							);
						}
					}
				}
				if ( preg_match_all( '/\[([^\]]+)\]/', $email_action['replyTo'], $matches ) ) {
					foreach ( $matches[1] as $match ) {
						if ( isset( $form_data[ $match ] ) ) {
							add_shortcode(
								$match,
								function() use ( $form_data, $match ) {
									return $form_data[ $match ];
								}
							);
						}
					}
				}
				if ( preg_match_all( '/\[([^\]]+)\]/', $email_action['name'], $matches ) ) {
					foreach ( $matches[1] as $match ) {
						if ( isset( $form_data[ $match ] ) ) {
							add_shortcode(
								$match,
								function() use ( $form_data, $match ) {
									return $form_data[ $match ];
								}
							);
						}
					}
				}
				if ( preg_match_all( '/\[([^\]]+)\]/', $email_action['subject'], $matches ) ) {
					foreach ( $matches[1] as $match ) {
						if ( isset( $form_data[ $match ] ) ) {
							add_shortcode(
								$match,
								function() use ( $form_data, $match ) {
									return $form_data[ $match ];
								}
							);
						}
					}
				}
			}
			add_shortcode(
				'admin_email',
				function() {
					return get_option( 'admin_email' );
				}
			);
		}

		// Send mail action
		if ( $res_data ) {
			if ( ! empty( $email_list ) ) {
				$body = $this->convert_form_data_into_html_for_email( $form_data );

				add_filter(
					'wp_mail_content_type',
					function () {
						return 'text/html';
					}
				);
				// Prev data
				$this->send_email_notification( $email_list, 'New ' . $form_name, $body );
			} elseif ( ! empty( $email_actions ) ) {
				add_filter(
					'wp_mail_content_type',
					function () {
						return 'text/html';
					}
				);

				foreach ( $email_actions as $email_action ) {
					$body    = $this->convert_form_data_into_html_for_email( $form_data );
					$replyTo = '';
					$name    = '';
					$subject = 'New ' . $form_name;
					$header  = array();

					if ( isset( $email_action['body'] ) ) {
						$body = '';
						foreach ( $email_action['body'] as $body_data ) {
							if ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'text' ) {
								$body = $body . $body_data['value'];
							} elseif ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'form' && isset( $form_data[ $body_data['value'] ] ) ) {
								$body = $body . $form_data[ $body_data['value'] ];
							}
						}

						$body = nl2br( $body );
					}

					if ( isset( $email_action['replyTo'] ) ) {
						$replyTo = do_shortcode( $email_action['replyTo'] );
					}
					if ( isset( $email_action['name'] ) ) {
						$name = do_shortcode( $email_action['name'] );
					}
					if ( isset( $email_action['subject'] ) ) {
						$subject = do_shortcode( $email_action['subject'] );
					}

					if ( strlen( $replyTo ) > 0 && strlen( $name ) > 0 ) {
						$header = array( 'Reply-To: ' . $name . ' <' . $replyTo . '>' );
					}

					$this->send_email_notification( do_shortcode( $email_action['emailList'] ), $subject, $body, $header );
				}
			}
		}

		// Mailclients Action
		if ( $has_email_field || ( $mail_clients && count( $mail_clients ) && isset( $mail_clients[0]['email_field'] ) ) ) {
			$email = $form_data['email'] ?? $form_data['Email'];
			if ( ( is_array( $mail_clients ) && count( $mail_clients ) ) || count( $mailclient_actions ) ) {
				$merge_fields = array();

				if ( is_array( $form_data ) ) {
					foreach ( $form_data as $key => $value ) {
						if ( self::matchFormField( $key, 'fullname' ) || self::matchFormField( $key, 'name' ) ) {
							$merge_fields['Fullname'] = $value;
						}

						if ( self::matchFormField( $key, 'firstname' ) || self::matchFormField( $key, 'fname' ) ) {
							$merge_fields['FNAME'] = $value;
						}

						if ( self::matchFormField( $key, 'lastname' ) || self::matchFormField( $key, 'lname' ) ) {
							$merge_fields['LNAME'] = $value;
						}

						if ( self::matchFormField( $key, 'birthday' ) || self::matchFormField( $key, 'bday' ) ) {
							$merge_fields['BIRTHDAY'] = $value;
						}

						if ( self::matchFormField( $key, 'birthday' ) || self::matchFormField( $key, 'bday' ) ) {
							$merge_fields['BIRTHDAY'] = $value;
						}
					}
				}

				if ( is_array( $mail_clients ) && count( $mail_clients ) ) {
					foreach ( $mail_clients as $mail_client ) {
						if ( isset( $mail_client['enabled'] ) && $mail_client['enabled'] ) {
							$mailclient_actions[] = $mail_client;
							$name                 = $mail_client['name'];
						}
					}
				}
				foreach ( $mailclient_actions as $mail_client ) {
					$name = $mail_client['name'];
					switch ( $name ) {

						default: {
								break;
						}
					}
				}
			}
		}

		if ( isset( $webhook_actions ) && count( $webhook_actions ) ) {
			foreach ( $webhook_actions as $webhook ) {
				if ( isset( $webhook['action'] ) && isset( $webhook['method'] ) ) {
					if ( $webhook['method'] === 'get' ) {
						$query_string = http_build_query( $form_data );

						if ( substr( $webhook['action'], -1 ) !== '/' ) {
							$webhook['action'] = $webhook['action'] . '/';
						}

						$url_with_query = $webhook['action'] . '?' . $query_string;
						$response       = HelperFunctions::http_get( $url_with_query );
						if ( is_wp_error( $response ) ) {
							$res_data = false;
						}
					} elseif ( $webhook['method'] === 'post' ) {
						$options = array(
							'method'      => 'POST',
							'httpversion' => '2.0',
							'headers'     => array(
								'Content-type' => 'application/x-www-form-urlencoded',
							),
							'body'        => $form_data,
						);

						$response = HelperFunctions::http_post( $webhook['action'], $options );
						if ( is_wp_error( $response ) ) {
							$res_data = false;
						}
					}
				}
			}
		}

		do_action( 'kirki_form_submitted', $form_data, $form_config );

		return rest_ensure_response( $res_data );
	}

	private function upload_file_to_media( $name ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( $name, 0 );

		return $attachment_id;
	}

	/**
	 * Convert form data into html for email
	 *
	 * @param array $form_data form all data.
	 *
	 * @return string html string.
	 */
	private function convert_form_data_into_html_for_email( $form_data = array() ) {
		$html = '<ul>';

		if ( is_array( $form_data ) ) {
			foreach ( $form_data as $key => $value ) {
				$html .= '<li>' . esc_html( $key ) . ': ' . esc_html( $value ) . '</li>';
			}
		}

		$html .= '</ul>';
		return $html;
	}

	/**
	 * Send email notification to admin email
	 *
	 * @param string|string[] $to          Array or comma-separated list of email addresses to send message.
	 * @param string          $subject     Email subject.
	 * @param string          $message     Message contents.
	 *
	 * @return void
	 */
	private function send_email_notification( $to, $subject, $message, $headers = array() ) {
		apply_filters( 'kirki_element_smtp', '' );
		wp_mail( $to, $subject, $message, $headers );
	}


	/**
	 * Check form submit/entry limit
	 *
	 * @param int $form_id form id.
	 * @param int $limit     Form limit.
	 * @return void wp_send_json.
	 */
	private function check_entry_limit( $form_id, $limit = null ) {
		if ( ! empty( $limit ) || is_numeric( $limit ) ) {
			global $wpdb;
			$session_id = session_id();
			$table_name = $wpdb->prefix . KIRKI_FORM_DATA_TABLE;
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "SELECT COUNT(DISTINCT timestamp) as total_entries FROM $table_name WHERE session_id=%s AND form_id=%s", array( $session_id, $form_id ) );
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$res           = $wpdb->get_results( $query, ARRAY_A );
			$total_entries = (int) $res[0]['total_entries'];

			if ( $total_entries >= $limit ) {
				wp_send_json( false );
				die();
			}
		}
	}

	/**
	 * Check response limit
	 *
	 * @param int $form_id form id.
	 * @param int $limit     Form limit.
	 * @return void wp_send_json.
	 */
	private function check_response_limit( $form_id, $limit = null ) {
		if ( ! empty( $limit ) || is_numeric( $limit ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . KIRKI_FORM_DATA_TABLE;
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "SELECT COUNT(DISTINCT timestamp) as total_entries FROM $table_name WHERE form_id=%s", array( $form_id ) );
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$res           = $wpdb->get_results( $query, ARRAY_A );
			$total_entries = (int) $res[0]['total_entries'];

			if ( $total_entries >= $limit ) {
				wp_send_json( false );
				die();
			}
		}
	}

	/**
	 * Retrieve a specific form
	 *
	 * @param string $post_id wp post id.
	 * @param string $form_id user form id.
	 * @return mixed|null current field id.
	 */
	private function get_form( $post_id, $form_id ) {
		if ( isset( $post_id, $form_id ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . KIRKI_FORM_TABLE;
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id=%d AND form_ele_id=%s", $post_id, $form_id ), ARRAY_A );

			return $field;
		}

		return null;
	}

	/**
	 * Insert a form
	 *
	 * @param string $post_id In which post/page the form resides.
	 * @param string $form_id ID of the form element.
	 * @param string $form_name Name of the form.
	 * @return string Inserted form ID.
	 */
	private function insert_form( $post_id, $form_id, $form_name ) {
		if ( isset( $post_id, $form_id, $form_name ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . KIRKI_FORM_TABLE,
				array(
					'post_id'     => (int) $post_id,
					'form_ele_id' => $form_id,
					'name'        => $form_name,
				),
				array(
					'%d',
					'%s',
					'%s',
				)
			);
			return $wpdb->insert_id;
		}

		return '';
	}

	/**
	 * Insert form data
	 *
	 * @param iterable|object $form_data form data.
	 * @param string          $form_id form id.
	 * @return int|Boolean
	 */
	private function insert_form_data( $form_data, $form_id, $form_data_types = array() ) {
		if ( isset( $form_data, $form_id ) && ! empty( $form_data ) ) {
			global $wpdb;
			$table_name    = $wpdb->prefix . KIRKI_FORM_DATA_TABLE;
			$timestamp     = time();
			$session_id    = session_id();
			$values        = array();
			$place_holders = array();
			$query         = "INSERT INTO $table_name (form_id, user_id, session_id, timestamp, input_key, input_value, input_type) VALUES ";
			$plholder_str  = '';

			foreach ( $form_data as $name => $value ) {
				$type = isset( $form_data_types[ $name ]['type'] ) ? $form_data_types[ $name ]['type'] : 'text';

				array_push(
					$values,
					$form_id,
					get_current_user_id(),
					$session_id,
					$timestamp,
					"$name",
					"$value",
					"$type"
				);

				$place_holders[] = '(%d, NULLIF(%d, 0), %s, %d, %s, %s, %s)';
			}

			$plholder_str = implode( ', ', $place_holders );

			$query .= $plholder_str;
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$sql = $wpdb->prepare( "$query ", $values );
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$res = $wpdb->query( $sql );
			return $res;
		}

		return false;
	}

	/**
	 * Search or match from filed
	 *
	 * @param string $field_name search column name.
	 * @param string $match_text search column value.
	 * @return boolean
	 */
	private static function matchFormField( $field_name = '', $match_text = '' ) {
		if ( ! empty( $field_name ) && ! empty( $match_text ) && strtolower( preg_replace( '/\s|_|-/', '', $field_name ) ) === $match_text ) {
			return true;
		}
		return false;
	}
}
