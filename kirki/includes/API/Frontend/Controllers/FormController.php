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

		// Verify reCAPTCHA if present
		$this->verify_recaptcha( $form_data );

		// Clean form data
		foreach ( $additional_keys as $key ) {
			unset( $form_data[ $key ] );
		}

		// Validate required parameters
		$form_meta_data_base64 = $request->get_param( '_kirki_form' );
		$wpnonce               = $request->get_param( '_wpnonce' );

		if ( ! isset( $form_meta_data_base64 ) || ! isset( $wpnonce ) ) {
			wp_send_json_error( 'Form data or wpnonce not found!', 400 );
			exit;
		}

		// Parse form metadata
		$metadata    = $this->parse_form_metadata( $form_meta_data_base64 );
		$form_id     = $metadata['form_id'];
		$post_id     = $metadata['post_id'];
		$form_config = HelperFunctions::get_session_data( $form_id );

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
			wp_send_json_error( 'Form data is invalid!', 400 );
			exit;
		}

		// Extract form configuration
		$form_name      = $form_config['name'];
		$max_entry      = $form_config['maxEntry'];
		$response_limit = $form_config['responseLimit'];
		$mail_clients   = isset( $form_config['mailClients'] ) ? $form_config['mailClients'] : null;
		$actions        = isset( $form_config['actions'] ) && is_array( $form_config['actions'] ) ? $form_config['actions'] : array();

		// Categorize actions
		$categorized_actions = $this->categorize_actions( $actions );
		$email_actions       = $categorized_actions['email'];
		$webhook_actions     = $categorized_actions['webhook'];
		$mailclient_actions  = $categorized_actions['mailclient'];

		// Handle legacy email notification
		$email_list = array();
		if ( isset( $form_config['emailNotification'] ) ) {
			$email_notification = $form_config['emailNotification'];
			$email_list         = $email_notification['enabled'] && isset( $email_notification['emailList'] ) && is_array( $email_notification['emailList'] ) ? $email_notification['emailList'] : null;
		}

		$entry_limit     = ! empty( $max_entry['restricted'] ) ? $max_entry['value'] : null;
		$response_limit  = ! empty( $response_limit['restricted'] ) ? $response_limit['value'] : null;
		$mail_clients    = is_array( $mail_clients ) ? $mail_clients : null;
		$has_email_field = ! empty( $form_data['email'] ) || ! empty( $form_data['Email'] );

		$res_data = false;

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

			// Process file uploads
			$form_data = $this->process_file_uploads( $form_data, $form_config['fields'] );

			// Save data
			$should_save_data = ! isset( $form_config['saveData'] ) || ! empty( $form_config['saveData'] );
			if ( isset( $saved_form_id ) && $should_save_data ) {
				$res = $this->insert_form_data( $form_data, $saved_form_id, $form_config['fields'] );
			} else {
				$res = true;
			}
			if ( isset( $res ) && $res !== false ) {
				$res_data = true;
			}
		}

		// Register shortcodes for email actions
		if ( ! empty( $email_actions ) ) {
			foreach ( $email_actions as $email_action ) {
				$this->register_shortcodes_from_field( $email_action['emailList'] ?? '', $form_data );
				$this->register_shortcodes_from_field( $email_action['replyTo'] ?? '', $form_data );
				$this->register_shortcodes_from_field( $email_action['name'] ?? '', $form_data );
				$this->register_shortcodes_from_field( $email_action['subject'] ?? '', $form_data );
			}
			add_shortcode(
				'admin_email',
				function() {
					return get_option( 'admin_email' );
				}
			);
		}

		// Process email actions (independent of data saving success)
		if ( ! empty( $email_list ) || ! empty( $email_actions ) ) {
			$this->process_email_actions( $email_list, $email_actions, $form_data, $form_name );
		}

		// Process mailclient actions
		if ( $has_email_field || ( $mail_clients && ! empty( $mail_clients ) ) ) {
			$this->process_mailclient_actions( $mail_clients, $mailclient_actions, $form_data );
		}

		// Process webhook actions
		if ( ! $this->process_webhook_actions( $webhook_actions, $form_data ) ) {
			$res_data = false;
		}

		do_action( 'kirki_form_submitted', $form_data, $form_config );

		return rest_ensure_response( $res_data );
	}

	/**
	 * Process file uploads from form submission
	 *
	 * @param array $form_data Form data array.
	 * @param array $form_fields Form field configuration.
	 * @return array Modified form data with attachment IDs.
	 */
	private function process_file_uploads( $form_data, $form_fields ) {
		if ( empty( $_FILES ) ) {
			return $form_data;
		}

		foreach ( $_FILES as $name => $file_data ) {
			if ( $file_data['error'] !== UPLOAD_ERR_OK ) {
				continue;
			}

			// Sanitize file info
			$_FILES[ $name ]['name']     = wp_unslash( $file_data['name'] );
			$_FILES[ $name ]['type']     = wp_unslash( $file_data['type'] );
			$_FILES[ $name ]['tmp_name'] = wp_unslash( $file_data['tmp_name'] );
			$_FILES[ $name ]['error']    = wp_unslash( $file_data['error'] );
			$_FILES[ $name ]['size']     = wp_unslash( $file_data['size'] );

			// Validate file
			$field_config = isset( $form_fields[ $name ] ) ? $form_fields[ $name ] : null;
			$file_type    = $_FILES[ $name ]['type'];
			$file_size    = $_FILES[ $name ]['size'];

			if ( $field_config ) {
				// Check MIME/type allowlist
				if ( ! empty( $field_config['allowed_types'] ) && ! in_array( $file_type, $field_config['allowed_types'], true ) ) {
					$form_data[ $name ] = new WP_Error(
						'invalid_file_type',
						sprintf( __( 'File type "%s" is not allowed for this field.', 'kirki' ), $file_type )
					);
					continue;
				}

				// Check max size
				if ( ! empty( $field_config['max_size'] ) && $file_size > $field_config['max_size'] ) {
					$form_data[ $name ] = new WP_Error(
						'file_too_large',
						sprintf( __( 'File size exceeds the maximum allowed for this field (%s bytes).', 'kirki' ), $field_config['max_size'] )
					);
					continue;
				}
			}

			// Upload file safely
			$attachment_id = $this->upload_file_to_media( $name );
			$form_data[ $name ] = $attachment_id; // Store attachment ID or WP_Error
		}

		return $form_data;
	}

	/**
	 * Upload file to WordPress media library
	 *
	 * @param string $name File input name.
	 * @return int|WP_Error Attachment ID or error.
	 */
	private function upload_file_to_media( $name ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		return media_handle_upload( $name, 0 );
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

				// Handle array values by serializing them
				if ( is_array( $value ) ) {
					$value = serialize( $value );
				}

				array_push(
					$values,
					$form_id,
					get_current_user_id(),
					$session_id,
					$timestamp,
					"$name",
					$value,
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
	 * Verify reCAPTCHA token
	 *
	 * @param array $form_data Form data containing reCAPTCHA token.
	 * @return void Exits on failure.
	 */
	private function verify_recaptcha( $form_data ) {
		if ( ! isset( $form_data['g-recaptcha-token'] ) ) {
			return;
		}

		$common_data = WpAdmin::get_common_data( true );
		
		if ( ! isset( $common_data['recaptcha']['GRC_version'] ) ) {
			wp_send_json_error( 'reCAPTCHA configuration not found', 400 );
			exit;
		}
		
		$version   = $common_data['recaptcha']['GRC_version'];
		$recaptcha = $common_data['recaptcha'][ $version ] ?? array();

		if ( empty( $recaptcha['GRC_secret_key'] ) ) {
			wp_send_json_error( 'reCAPTCHA secret key not configured', 400 );
			exit;
		}

		$is_valid = $this->verifyGoogleRecaptchaToken( $recaptcha['GRC_secret_key'], $form_data['g-recaptcha-token'] );
		if ( ! $is_valid ) {
			wp_send_json_error( 'Google reCAPTCHA verification failed', 400 );
			exit;
		}
	}

	/**
	 * Parse and validate form metadata
	 *
	 * @param string $form_meta_data_base64 Base64 encoded form metadata.
	 * @return array Array with form_id and post_id.
	 */
	private function parse_form_metadata( $form_meta_data_base64 ) {
		//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$form_meta_data = explode( '|', base64_decode( base64_decode( $form_meta_data_base64 ) ) );
		
		return array(
			'form_id' => isset( $form_meta_data[0] ) ? $form_meta_data[0] : null,
			'post_id' => isset( $form_meta_data[1] ) ? $form_meta_data[1] : null,
		);
	}

	/**
	 * Categorize actions by type
	 *
	 * @param array $actions Form actions configuration.
	 * @return array Categorized actions.
	 */
	private function categorize_actions( $actions ) {
		$email_actions      = array();
		$webhook_actions    = array();
		$mailclient_actions = array();

		if ( is_array( $actions ) && ! empty( $actions ) ) {
			foreach ( $actions as $action ) {
				if ( ! isset( $action['type'] ) ) {
					continue;
				}

				switch ( $action['type'] ) {
					case 'email':
						$email_actions[] = $action;
						break;
					case 'webhooks':
						$webhook_actions[] = $action;
						break;
					case 'mailclients':
						$mailclient_actions[] = $action;
						break;
				}
			}
		}

		return array(
			'email'      => $email_actions,
			'webhook'    => $webhook_actions,
			'mailclient' => $mailclient_actions,
		);
	}

	/**
	 * Register shortcodes from email action field
	 *
	 * @param string $field_value The field value containing shortcodes.
	 * @param array  $form_data   The form data to use for shortcode values.
	 * @return void
	 */
	private function register_shortcodes_from_field( $field_value, $form_data ) {
		if ( ! isset( $field_value ) || empty( $field_value ) ) {
			return;
		}

		if ( preg_match_all( '/\[([^\]]+)\]/', $field_value, $matches ) ) {
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

	/**
	 * Process and send emails
	 *
	 * @param array  $email_list   Legacy email list.
	 * @param array  $email_actions Email actions configuration.
	 * @param array  $form_data    Form data.
	 * @param string $form_name    Form name.
	 * @return void
	 */
	private function process_email_actions( $email_list, $email_actions, $form_data, $form_name ) {
		// Set email content type to HTML
		add_filter(
			'wp_mail_content_type',
			function () {
				return 'text/html';
			}
		);

		if ( ! empty( $email_list ) ) {
			$body = $this->convert_form_data_into_html_for_email( $form_data );
			$this->send_email_notification( $email_list, 'New ' . $form_name, $body );
		} elseif ( ! empty( $email_actions ) ) {
			foreach ( $email_actions as $email_action ) {
				$this->send_single_email_action( $email_action, $form_data, $form_name );
			}
		}
	}

	/**
	 * Send a single email action
	 *
	 * @param array  $email_action Email action configuration.
	 * @param array  $form_data    Form data.
	 * @param string $form_name    Form name.
	 * @return void
	 */
	private function send_single_email_action( $email_action, $form_data, $form_name ) {
		$body    = $this->convert_form_data_into_html_for_email( $form_data );
		$replyTo = '';
		$name    = '';
		$subject = 'New ' . $form_name;
		$header  = array();

		if ( isset( $email_action['body'] ) && is_array( $email_action['body'] ) ) {
			$body = $this->build_email_body( $email_action['body'], $form_data );
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

		if ( isset( $email_action['emailList'] ) && ! empty( $email_action['emailList'] ) ) {
			$this->send_email_notification( do_shortcode( $email_action['emailList'] ), $subject, $body, $header );
		}
	}

	/**
	 * Build email body from body configuration
	 *
	 * @param array $body_config Body configuration.
	 * @param array $form_data   Form data.
	 * @return string Email body HTML.
	 */
	private function build_email_body( $body_config, $form_data ) {
		$body_parts = array();
		foreach ( $body_config as $body_data ) {
			if ( ! isset( $body_data['type'], $body_data['value'] ) ) {
				continue;
			}
			if ( $body_data['type'] === 'text' ) {
				$body_parts[] = $body_data['value'];
			} elseif ( $body_data['type'] === 'form' && isset( $form_data[ $body_data['value'] ] ) ) {
				$body_parts[] = $form_data[ $body_data['value'] ];
			}
		}
		return nl2br( implode( '', $body_parts ) );
	}

	/**
	 * Process webhook actions
	 *
	 * @param array $webhook_actions Webhook actions configuration.
	 * @param array $form_data       Form data.
	 * @return bool Success status.
	 */
	private function process_webhook_actions( $webhook_actions, $form_data ) {
		if ( empty( $webhook_actions ) ) {
			return true;
		}

		$success = true;
		foreach ( $webhook_actions as $webhook ) {
			if ( ! isset( $webhook['action'], $webhook['method'] ) ) {
				continue;
			}

			if ( $webhook['method'] === 'get' ) {
				$success = $this->send_webhook_get( $webhook['action'], $form_data ) && $success;
			} elseif ( $webhook['method'] === 'post' ) {
				$success = $this->send_webhook_post( $webhook['action'], $form_data ) && $success;
			}
		}

		return $success;
	}

	/**
	 * Send GET webhook request
	 *
	 * @param string $url       Webhook URL.
	 * @param array  $form_data Form data.
	 * @return bool Success status.
	 */
	private function send_webhook_get( $url, $form_data ) {
		$query_string = http_build_query( $form_data );

		if ( substr( $url, -1 ) !== '/' ) {
			$url = $url . '/';
		}

		$url_with_query = $url . '?' . $query_string;
		$response       = HelperFunctions::http_get( $url_with_query );
		
		return ! is_wp_error( $response );
	}

	/**
	 * Send POST webhook request
	 *
	 * @param string $url       Webhook URL.
	 * @param array  $form_data Form data.
	 * @return bool Success status.
	 */
	private function send_webhook_post( $url, $form_data ) {
		$options = array(
			'method'      => 'POST',
			'httpversion' => '2.0',
			'headers'     => array(
				'Content-type' => 'application/x-www-form-urlencoded',
			),
			'body'        => $form_data,
		);

		$response = HelperFunctions::http_post( $url, $options );
		
		return ! is_wp_error( $response );
	}

	/**
	 * Process mailclient actions
	 *
	 * @param array $mail_clients       Mail clients configuration.
	 * @param array $mailclient_actions Mailclient actions.
	 * @param array $form_data          Form data.
	 * @return void
	 */
	private function process_mailclient_actions( $mail_clients, $mailclient_actions, $form_data ) {
		$email = isset( $form_data['email'] ) ? $form_data['email'] : ( isset( $form_data['Email'] ) ? $form_data['Email'] : null );
		
		if ( ! $email ) {
			return;
		}

		$merge_fields = $this->extract_merge_fields( $form_data );

		// Merge legacy mail_clients into mailclient_actions
		if ( is_array( $mail_clients ) && ! empty( $mail_clients ) ) {
			foreach ( $mail_clients as $mail_client ) {
				if ( isset( $mail_client['enabled'] ) && $mail_client['enabled'] ) {
					$mailclient_actions[] = $mail_client;
				}
			}
		}

		// Process mailclient actions
		foreach ( $mailclient_actions as $mail_client ) {
			do_action( 'kirki_mailclient_action', $mail_client, $email, $merge_fields );
		}
	}

	/**
	 * Extract merge fields from form data
	 *
	 * @param array $form_data Form data.
	 * @return array Merge fields.
	 */
	private function extract_merge_fields( $form_data ) {
		$merge_fields = array();

		if ( ! is_array( $form_data ) ) {
			return $merge_fields;
		}

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
		}

		return $merge_fields;
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
