<?php

namespace KirkiComponentLib\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\Ajax\Page;
use Kirki\HelperFunctions;
use WP_REST_Server;
use WP_REST_Controller;
use WP_REST_Response;

class CompLibFormHandler extends WP_REST_Controller {

	protected $namespace = KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '/v1';

	public function __construct() {
		$this->init_rest_api_endpoint( 'kirki-login', WP_REST_Server::CREATABLE, array( $this, 'handle_login' ), array( $this, 'guest_permissions_check' ) );
		$this->init_rest_api_endpoint( 'kirki-register', WP_REST_Server::CREATABLE, array( $this, 'handle_register' ), array( $this, 'guest_permissions_check' ) );
		$this->init_rest_api_endpoint( 'kirki-forgot-password', WP_REST_Server::CREATABLE, array( $this, 'handle_forgot_password' ), array( $this, 'guest_permissions_check' ) );
		$this->init_rest_api_endpoint( 'kirki-change-password', WP_REST_Server::CREATABLE, array( $this, 'handle_change_password' ), array( $this, 'guest_permissions_check' ) );
		$this->init_rest_api_endpoint( 'kirki-retrieve-username', WP_REST_Server::CREATABLE, array( $this, 'handle_retrieve_username' ), array( $this, 'guest_permissions_check' ) );
		$this->init_rest_api_endpoint( 'kirki-comment', WP_REST_Server::CREATABLE, array( $this, 'handle_post_comment' ), array( $this, 'comment_permissions_check' ) );
	}

	public function init_rest_api_endpoint( $endpoint, $methods, $callback, $permission_callback = null ) {
		add_action(
			'rest_api_init',
			function () use ( $endpoint, $methods, $callback, $permission_callback ) {
				register_rest_route(
					$this->namespace,
					'/' . $endpoint,
					array(
						array(
							'methods'             => $methods,
							'callback'            => $callback,
							'permission_callback' => $permission_callback ? $permission_callback : array( $this, 'get_item_permissions_check' ),
							'args'                => $this->get_endpoint_args_for_item_schema( $methods ),
						),
						'schema' => array( $this, 'get_item_schema' ),
					)
				);
			}
		);
	}

	public function get_item_permissions_check( $request ) {
		return true;
	}

	public function guest_permissions_check( $request ) {
		return true;
	}

	public function comment_permissions_check( $request ) {
		if ( ! is_user_logged_in() && get_option( 'default_comment_status' ) !== 'open' ) {
        return new \WP_Error(
            'rest_forbidden',
            __( 'You must be logged in to post comments.' ),
            array( 'status' => 401 )
        );
    }
    return true;
	}

	private function wp_unique_username( $username, $suffix = 1 ) {
		$original_username = $username;
		while ( username_exists( $username ) ) {
			$username = sprintf( '%s_%d', $original_username, $suffix++ );
		}
		return $username;
	}

	private function validate_meta_field( $field_name ) {
		$allowed_meta_fields = apply_filters( 'kirki_allowed_registration_meta_fields', array(
			'first_name',
			'last_name',
			'phone',
			'company',
			'address',
			'city',
			'state',
			'country',
			'zip',
		) );
		
		if ( ! in_array( $field_name, $allowed_meta_fields, true ) ) {
			return false;
		}
		
		if ( preg_match( '/[^a-z0-9_-]/i', $field_name ) ) {
			return false;
		}
		
		return true;
	}

	/**
	 * Verify that the submitted emailSubject + emailBody were signed by the server
	 * at page-render time and have not been tampered with.
	 *
	 * IMPORTANT: $body_raw must be the raw JSON string as received from the request —
	 * never a re-encoded array. Re-encoding can produce different output than the
	 * original wp_json_encode() call, breaking the HMAC comparison.
	 *
	 * @param string $subject   The email subject string.
	 * @param string $body_raw  The raw emailBody JSON string from the request.
	 * @param string $signature The HMAC signature to verify against.
	 * @return bool
	*/
	private function verify_email_template_signature( $subject, $body_raw, $signature ) {
		if ( empty( $signature ) ) {
				return false;
		}

		// Use the raw string directly — same as what was signed in ElementGenerator.
		// Do NOT json_decode then re-encode here.
		$payload  = $subject . '|' . $body_raw;
		$secret   = AUTH_KEY . AUTH_SALT;
		$expected = hash_hmac( 'sha256', $payload, $secret );

		return hash_equals( $expected, $signature );
	}

	/**
	 * Build the email body from a verified emailBody definition.
	 * Chip values are resolved from a fixed server-controlled map.
	 *
	 * @param array $email_body_array
	 * @param array $chip_data
	 * @return string
	 */
	private function build_email_body( array $email_body_array, array $chip_data ) {
		$email_body = '';
		foreach ( $email_body_array as $body_data ) {
				if ( ! isset( $body_data['type'], $body_data['value'] ) ) {
						continue;
				}
				if ( $body_data['type'] === 'text' ) {
						$email_body .= $body_data['value'];
				} elseif ( $body_data['type'] === 'chip' && isset( $chip_data[ $body_data['value'] ] ) ) {
						$email_body .= $chip_data[ $body_data['value'] ];
				}
		}
		return $email_body;
	}

	public function handle_post_comment( $request ) {
    $form_data     = $request->get_body_params();
    $transient_name = $this->validate_nonce( 'kirki-comment' );  // note: typo fix from $transiet_name

    $comment        = isset( $form_data['comment'] ) ? sanitize_text_field( $form_data['comment'] ) : '';
    $post_id        = isset( $form_data['post_id'] ) ? absint( $form_data['post_id'] ) : 0;
    $comment_parent = isset( $form_data['comment_parent'] ) ? absint( $form_data['comment_parent'] ) : 0;
    $user_id        = get_current_user_id();
    $user           = $user_id ? get_user_by( 'ID', $user_id ) : null;

    // Resolve author identity.
    if ( $user ) {
			$name  = $user->get( 'display_name' );
			$email = $user->get( 'user_email' );
    } else {
			// Anonymous commenter: require name + valid email supplied in the form.
			$name  = isset( $form_data['name'] )  ? sanitize_text_field( $form_data['name'] )  : '';
			$email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] )       : '';

			if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
				return new WP_REST_Response(
					array( 'message' => 'Name and a valid email address are required.' ),
					400
				);
			}
    }

    $existing_comment_id = isset( $form_data['comment_id'] ) ? absint( $form_data['comment_id'] ) : 0;
    $is_edit             = $existing_comment_id !== 0;
    $collection_type     = isset( $form_data['collection_type'] ) ? sanitize_text_field( $form_data['collection_type'] ) : '';

    // -----------------------------------------------------------------------
    // EDIT PATH
    // -----------------------------------------------------------------------
    if ( $is_edit ) {
			// FIX 1: Editing always requires an authenticated session.
			if ( ! is_user_logged_in() ) {
				return new WP_REST_Response(
						array( 'message' => 'You must be logged in to edit a comment.' ),
						401
				);
			}

			$existing_comment = get_comment( $existing_comment_id );

			if ( ! $existing_comment ) {
				return new WP_REST_Response(
						array( 'message' => 'Comment not found.' ),
						404
				);
			}

			// FIX 1 (cont.): strict ownership — user_id 0 must never match.
			$is_owner    = ( $user_id !== 0 && (int) $existing_comment->user_id === $user_id );
			$is_moderator = current_user_can( 'moderate_comments' );

			if ( ! $is_owner && ! $is_moderator ) {
				return new WP_REST_Response(
					array( 'message' => 'You are not authorized to edit this comment.' ),
					403
				);
			}

			$date = gmdate( 'Y-m-d H:i:s' );

			global $wpdb;
			$wpdb->update(
				$wpdb->comments,
				array(
					'comment_content'  => $comment,
					'comment_date'     => $date,
					'comment_date_gmt' => get_gmt_from_date( $date ),
				),
				array( 'comment_ID' => $existing_comment_id )
			);

			apply_filters(
				'kirki_comment_added-' . $collection_type,
				array(
					'comment_ID' => $existing_comment_id,
					'user_id'    => $user_id,
					'form_data'  => $form_data,
				)
			);

			delete_transient( $transient_name );
			return new WP_REST_Response( array( 'message' => 'Comment updated.' ), 200 );
    }

    // -----------------------------------------------------------------------
    // INSERT PATH
    // -----------------------------------------------------------------------

    // FIX 2: Build the comment array without hardcoding comment_approved=1,
    // then route through wp_new_comment() so WordPress moderation, spam
    // filters (Akismet, etc.), and flood checks all apply normally.
    $comment_data = array(
			'comment_post_ID'      => $post_id,
			'user_id'              => $user_id,
			'comment_author'       => $name,
			'comment_author_email' => $email,
			'comment_author_IP'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'comment_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'comment_content'      => $comment,
			'comment_parent'       => $comment_parent,
    );

    $comment_data = apply_filters( 'kirki_comment-' . $collection_type, $comment_data );

    // wp_new_comment() runs duplicate/flood/spam checks, fires hooks, and
    // respects the site's moderation settings.
    $comment_id = wp_new_comment( $comment_data, true );  // true = return WP_Error on failure

    if ( is_wp_error( $comment_id ) ) {
			return new WP_REST_Response(
					array( 'message' => $comment_id->get_error_message() ),
					400
			);
    }

    if ( ! $comment_id ) {
			return new WP_REST_Response(
					array( 'message' => 'Failed to add comment.' ),
					400
			);
    }

    apply_filters(
			'kirki_comment_added-' . $collection_type,
			array(
					'comment_ID' => $comment_id,
					'user_id'    => $user_id,
					'form_data'  => $form_data,
			)
    );

    delete_transient( $transient_name );
    return new WP_REST_Response( array( 'message' => 'Comment added.' ), 200 );
	}



	public function handle_login( $request ) {
		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-login' );

		$username = isset( $form_data['username'] ) ? sanitize_text_field( $form_data['username'] ) : '';
		$password = isset( $form_data['password'] ) ? sanitize_text_field( $form_data['password'] ) : '';
		$email    = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';

		if ( strlen( $username ) === 0 && isset( $form_data['email'] ) && strlen( $email ) > 0 ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$username = $user->get( 'user_login' );
			} else {
				$response = array(
					'message' => 'Invalid username or password',
				);
				return new WP_REST_Response( $response, 401 );
			}
		}

		if (
		isset( $username ) && strlen( $username ) > 0 &&
		isset( $password ) && strlen( $password ) > 0
		) {
			$user = wp_signon(
				array(
					'user_login'    => $username,
					'user_password' => $password,
					'remember'      => true,
				)
			);

			if ( is_wp_error( $user ) ) {
				$response = array(
					'message' => 'Invalid username or password',
				);
				return new WP_REST_Response( $response, 401 );
			}
			$response = array(
				'message' => 'User logged in',
				'user'    => array(
					'username'     => $user->get( 'user_login' ),
					'id'           => $user->get( 'ID' ),
					'display_name' => $user->get( 'display_name' ),
					'email'        => $user->get( 'user_email' ),
					'user_type'    => $user->get( 'user_type' ),
				),
			);
			delete_transient( $transiet_name );
			return new WP_REST_Response( $response, 200 );
		}
		$response = array(
			'message' => 'Invalid form data',
		);
		return new WP_REST_Response( $response, 400 );
	}

	public function handle_register( $request ) {
		$can_register = get_option( 'users_can_register' );
		if ( $can_register !== '1' ) {
			$response = array(
				'message' => 'User not allowed to register',
			);
			return new WP_REST_Response( $response, 500 );
		};

		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-register' );

		$username = isset( $form_data['username'] ) ? sanitize_text_field( $form_data['username'] ) : '';
		$email    = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';
		$password = isset( $form_data['password'] ) ? sanitize_text_field( $form_data['password'] ) : '';

		if ( strlen( $email ) > 0 && strlen( $username ) === 0 ) {
			preg_match( '/^(.*?)@/', $email, $matches );
			$username = $this->wp_unique_username( $matches[1] );
		}

		$user_data = array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'meta_input' => array(),
		);

		foreach ( $form_data as $name => $value ) {
			if ( $name !== 'username' && $name !== 'email' && $name !== 'password' && $name !== 'confirm_password' ) {
				if ( $this->validate_meta_field( $name ) ) {
					$user_data['meta_input'][ KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '_' . $name ] = sanitize_text_field( $value );
				}
			}
		}

		if (
		isset( $username ) && strlen( $username ) > 0
		&& isset( $email ) && strlen( $email ) > 0 &&
		isset( $password ) && strlen( $password ) > 0
		) {
			$id = wp_insert_user( $user_data );

			if ( is_wp_error( $id ) ) {
				$response = array(
					'message' => $id->errors[ array_key_first( $id->errors ) ],
				);
				return new WP_REST_Response( $response, 500 );
			}

			wp_new_user_notification( $id, null, 'both' );
			$response = array(
				'message' => 'User created',
				'user_id' => $id,
			);
			delete_transient( $transiet_name );
			return new WP_REST_Response( $response, 200 );
		}
		$response = array(
			'message' => 'Invalid form data',
		);
		return new WP_REST_Response( $response, 400 );
	}

	public function handle_forgot_password( $request ) {
		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-forgot-password' );

		$email    = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';
		$username = isset( $form_data['username'] ) ? sanitize_text_field( $form_data['username'] ) : '';

		if ( strlen( $username ) === 0 && isset( $form_data['email'] ) && strlen( $email ) > 0 ) {
			$user = get_user_by( 'email', $email );

			if ( ! $user ) {
				return new WP_REST_Response( array( 'message' => 'If an account exists with this email, you will receive a password reset link.' ), 200 );
			}

			$username = $user->get( 'user_login' );
		}

		if ( empty( $username ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid request' ), 400 );
		}

		if ( isset( $username ) && strlen( $username ) > 0 ) {
			$user = get_user_by( 'login', $username );

			if ( ! $user ) {
				$response = array(
					'message' => 'If an account exists with this information, you will receive a password reset link.',
				);
				return new WP_REST_Response( $response, 200 );
			}

			$user_email = $user->get( 'user_email' );
			if($email !== $user_email) {
				$response = array(
					'message' => 'If an account exists with this information, you will receive a password reset link.',
				);
				return new WP_REST_Response( $response, 200 );
			}
			$email = $user_email;

			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				$response = array(
					'message' => $key->get_error_message(),
				);
				return new WP_REST_Response( $response, 500 );
			}

			// Prepare email content.
			$url = HelperFunctions::get_utility_page_url( Page::TYPE_FORGOT_PASSWORD );

			$username  = $user->user_login;
			$chip_data = array(
				'username'    => $username,
				'email'       => $email,
				'displayname' => $user->display_name,
				'sitename'    => get_bloginfo( 'name' ),
				'reset_link'  => "$url?action=rp&key=$key&login=" . rawurlencode( $username ),
			);

			$email_subject   = isset( $form_data['emailSubject'] ) ? $form_data['emailSubject'] : '';
			$email_body_raw  = isset( $form_data['emailBody'] ) ? $form_data['emailBody'] : '[]';
			$email_signature = isset( $form_data['emailSignature'] ) ? $form_data['emailSignature'] : '';

			if ( ! $this->verify_email_template_signature( $email_subject, $email_body_raw, $email_signature ) ) {
				wp_send_json_error( array( 'message' => 'Invalid request' ), 400 );
				exit;
			}

			$email_body_array = json_decode( $email_body_raw, true );
			if ( ! is_array( $email_body_array ) ) {
				$email_body_array = array();
			}

			$email_body = $this->build_email_body( $email_body_array, $chip_data );

			$email_body = nl2br( $email_body );

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			// Send custom email.
			apply_filters( 'kirki_element_smtp', '' );
			$sent = wp_mail( $email, sanitize_text_field( $email_subject ), $email_body, $headers );

			if ( $sent ) {
				$response = array(
					'message' => 'Email sent',
				);
				delete_transient( $transiet_name );
				return new WP_REST_Response( $response, 200 );
			} else {
				$response = array(
					'message' => 'Failed to send email',
				);
				return new WP_REST_Response( $response, 500 );
			}
		}

		$response = array(
			'message' => 'Invalid request',
		);
		return new WP_REST_Response( $response, 400 );
	}

	public function handle_change_password( $request ) {
		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-change-password' );

		$username         = isset( $form_data['username'] ) ? sanitize_text_field( $form_data['username'] ) : '';
		$reset_key        = isset( $form_data['reset_key'] ) ? sanitize_text_field( $form_data['reset_key'] ) : '';
		$new_password     = isset( $form_data['new_password'] ) ? sanitize_text_field( $form_data['new_password'] ) : '';
		$confirm_password = isset( $form_data['confirm_password'] ) ? sanitize_text_field( $form_data['confirm_password'] ) : '';

		if ( empty( $reset_key ) || empty( $username ) || empty( $new_password ) || empty( $confirm_password ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ), 400 );
			exit;
		}

		if ( $new_password !== $confirm_password ) {
			wp_send_json_error( array( 'message' => 'Passwords do not match.' ), 400 );
			exit;
		}

		$user = check_password_reset_key( $reset_key, $username );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => $user->get_error_message() ), 400 );
			exit;
		}

		wp_set_password( $new_password, $user->ID );
		delete_transient( $transiet_name );
		wp_send_json_success( array( 'message' => 'Password reset successfully.' ) );
		exit;
	}

	public function handle_retrieve_username( $request ) {
		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-retrieve-username' );

		$email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address.' ), 400 );
			exit;
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			wp_send_json_success( array( 'message' => 'If an account exists with this email, you will receive your username.' ) );
			exit;
		}

		$username  = $user->user_login;
		$chip_data = array(
			'username'    => $username,
			'email'       => $email,
			'displayname' => $user->display_name,
			'sitename'    => get_bloginfo( 'name' ),
		);

		$email_subject   = isset( $form_data['emailSubject'] ) ? $form_data['emailSubject'] : '';
		$email_body_raw  = isset( $form_data['emailBody'] ) ? $form_data['emailBody'] : '[]';
		$email_signature = isset( $form_data['emailSignature'] ) ? $form_data['emailSignature'] : '';

		if ( ! $this->verify_email_template_signature( $email_subject, $email_body_raw, $email_signature ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request' ), 400 );
			exit;
		}

		$email_body_array = json_decode( $email_body_raw, true );
		if ( ! is_array( $email_body_array ) ) {
			$email_body_array = array();
		}

		$email_body = $this->build_email_body( $email_body_array, $chip_data );
		
		$email_body = nl2br( $email_body );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		apply_filters( 'kirki_element_smtp', '' );
		$email_sent = wp_mail( $email, sanitize_text_field( $email_subject ), $email_body, $headers );

		if ( ! $email_sent ) {
			wp_send_json_error( array( 'message' => 'Failed to send email. Please try again later.' ), 500 );
			exit;
		}

		delete_transient( $transiet_name );
		wp_send_json_success( array( 'message' => 'Username sent to your email address.' ) );
		exit;
	}

	/**
	 * Validate the nonce from the request header and return true on success.
	 * Exits with an error response on failure.
	 *
	 * @param string $element_name
	 * @return true
	 */
	public function validate_nonce( $element_name ) {
		$nonce = isset( $_SERVER['HTTP_X_WP_ELEMENT_NONCE'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_ELEMENT_NONCE'] ) )
		: null;

		if ( ! $nonce ) {
			wp_send_json_error( 'Missing nonce', 400 );
			exit;
		}

		$action = KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '_' . $element_name;

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( 'Not authorized', 400 );
			exit;
		}

		return true;
	}
}

new CompLibFormHandler();
