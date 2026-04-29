<?php

namespace KirkiComponentLib\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;
use WP_REST_Server;
use WP_REST_Controller;
use WP_REST_Response;

class CompLibFormHandler extends WP_REST_Controller {

	protected $namespace = KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '/v1';

	public function __construct() {
		$this->init_rest_api_endpoint( 'kirki-login', WP_REST_Server::CREATABLE, array( $this, 'handle_login' ) );
		$this->init_rest_api_endpoint( 'kirki-register', WP_REST_Server::CREATABLE, array( $this, 'handle_register' ) );
		$this->init_rest_api_endpoint( 'kirki-forgot-password', WP_REST_Server::CREATABLE, array( $this, 'handle_forgot_password' ) );
		$this->init_rest_api_endpoint( 'kirki-change-password', WP_REST_Server::CREATABLE, array( $this, 'handle_change_password' ) );
		$this->init_rest_api_endpoint( 'kirki-retrieve-username', WP_REST_Server::CREATABLE, array( $this, 'handle_retrieve_username' ) );
		$this->init_rest_api_endpoint( 'kirki-comment', WP_REST_Server::CREATABLE, array( $this, 'handle_post_comment' ) );
	}

	public function init_rest_api_endpoint( $endpoint, $methods, $callback ) {
		add_action(
			'rest_api_init',
			function () use ( $endpoint, $methods, $callback ) {
				register_rest_route(
					$this->namespace,
					'/' . $endpoint,
					array(
						array(
							'methods'             => $methods,
							'callback'            => $callback,
							'permission_callback' => array( $this, 'get_item_permissions_check' ),
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

	private function wp_unique_username( $username, $suffix = 1 ) {
		$original_username = $username;
		while ( username_exists( $username ) ) {
			$username = sprintf( '%s_%d', $original_username, $suffix++ );
		}
		return $username;
	}

	public function handle_post_comment( $request ) {
		$form_data     = $request->get_body_params();
		$transiet_name = $this->validate_nonce( 'kirki-comment' );

		$name           = isset( $form_data['name'] ) ? sanitize_text_field( $form_data['name'] ) : '';
		$email          = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';
		$comment        = isset( $form_data['comment'] ) ? sanitize_text_field( $form_data['comment'] ) : '';
		$post_id        = isset( $form_data['post_id'] ) ? sanitize_text_field( $form_data['post_id'] ) : 0;
		$comment_parent = isset( $form_data['comment_parent'] ) ? sanitize_text_field( $form_data['comment_parent'] ) : 0;
	  $date    = gmdate( 'Y-m-d H:i:s' );
		$user_id        = get_current_user_id();
		$user           = get_user_by( 'ID', $user_id );
		if ( $user ) {
			$name  = $user->get( 'display_name' );
			$email = $user->get( 'user_email' );
		}

		$existing_comment_id = isset( $form_data['comment_id'] ) ? sanitize_text_field( $form_data['comment_id'] ) : 0;
		$is_edit             = 0 == $existing_comment_id ? false : true;
		$collection_type     = isset( $form_data['collection_type'] ) ? sanitize_text_field( $form_data['collection_type'] ) : '';

		global $wpdb;
		if ( $is_edit ) {
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
		} else {
			$comment_data = array(
				'comment_post_ID'      => $post_id,
				'user_id'              => $user_id,
				'comment_author'       => $name,
				'comment_author_email' => $email,
				'comment_content'      => $comment,
				'comment_parent'       => $comment_parent,
				'comment_approved'     => 1,
				'comment_date'         => $date,
				'comment_date_gmt'     => get_gmt_from_date( $date ),
			);
			$comment_data = apply_filters( 'kirki_comment-' . $collection_type, $comment_data );
			$wpdb->insert( $wpdb->comments, $comment_data );
			$comment_id = (int) $wpdb->insert_id;
			apply_filters(
				'kirki_comment_added-' . $collection_type,
				array(
					'comment_ID' => $comment_id,
					'user_id'    => $user_id,
					'form_data'  => $form_data,
				)
			);
		}

		// Check if the comment was added successfully.
		if ( $comment_id ) {
			$response = array(
				'message' => 'Comment Added',
			);
			delete_transient( $transiet_name );
			return new WP_REST_Response( $response, 200 );
		} else {
			$response = array(
				'message' => 'Invalid form data',
			);
			return new WP_REST_Response( $response, 400 );
		}
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
					'message' => 'User not found',
				);
				return new WP_REST_Response( $response, 404 );
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
					'message' => $user->errors[ array_key_first( $user->errors ) ],
				);
				return new WP_REST_Response( $response, 500 );
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
				$user_data['meta_input'][ KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '_' . $name ] = $value;
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
			if ( $user ) {
				$username = $user->get( 'user_login' );
			} else {
				$response = array(
					'message' => 'User not found',
				);
				return new WP_REST_Response( $response, 404 );
			}
		}

		if ( isset( $username ) && strlen( $username ) > 0 ) {
			$user = get_user_by( 'login', $username );

			if ( ! $user ) {
				$response = array(
					'message' => 'User not found',
				);
				return new WP_REST_Response( $response, 404 );
			}

			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				$response = array(
					'message' => $key->get_error_message(),
				);
				return new WP_REST_Response( $response, 500 );
			}

			// Prepare email content.
			$url = HelperFunctions::get_utility_page_url( 'reset_password' );

			$username  = $user->user_login;
			$chip_data = array(
				'username'    => $username,
				'email'       => $email,
				'displayname' => $user->display_name,
				'sitename'    => get_bloginfo( 'name' ),
				'reset_link'  => "$url?action=rp&key=$key&login=" . rawurlencode( $username ),
			);

			$email_subject = isset( $form_data['emailSubject'] ) ? sanitize_text_field( $form_data['emailSubject'] ) : '';
			$email_body    = '';

			if ( isset( $form_data['emailBody'] ) ) {
				$email_body = json_decode( $form_data['emailBody'], true );
				foreach ( $email_body as $key => $body_data ) {
					if ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'text' ) {
						$email_body = $email_body . $body_data['value'];
					} elseif ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'chip' ) {
						$email_body = $email_body . $chip_data[ $body_data['value'] ];
					}
				}
			}

			$email_body = nl2br( $email_body );

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			// Send custom email.
			apply_filters( 'kirki_element_smtp', '' );
			$sent = wp_mail( $email, $email_subject, $email_body, $headers );

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
			wp_send_json_error( array( 'message' => 'No user found with that email address.' ), 404 );
			exit;
		}

		$username  = $user->user_login;
		$chip_data = array(
			'username'    => $username,
			'email'       => $email,
			'displayname' => $user->display_name,
			'sitename'    => get_bloginfo( 'name' ),
		);

		$email_subject = isset( $form_data['emailSubject'] ) ? sanitize_text_field( $form_data['emailSubject'] ) : '';
		$email_body    = '';

		if ( isset( $form_data['emailBody'] ) ) {
			$email_body = json_decode( $form_data['emailBody'], true );
			foreach ( $email_body as $key => $body_data ) {
				if ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'text' ) {
					$email_body = $email_body . $body_data['value'];
				} elseif ( isset( $body_data['type'] ) && isset( $body_data['value'] ) && $body_data['type'] === 'chip' ) {
					$email_body = $email_body . $chip_data[ $body_data['value'] ];
				}
			}
		}

		$email_body = nl2br( $email_body );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		apply_filters( 'kirki_element_smtp', '' );
		$email_sent = wp_mail( $email, $email_subject, $email_body, $headers );

		if ( ! $email_sent ) {
			wp_send_json_error( array( 'message' => 'Failed to send email. Please try again later.' ), 500 );
			exit;
		}

		delete_transient( $transiet_name );
		wp_send_json_success( array( 'message' => 'Username sent to your email address.' ) );
		exit;
	}

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
