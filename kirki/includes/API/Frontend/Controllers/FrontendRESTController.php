<?php
/**
 * FrontendRESTController
 *
 * @package kirki
 */

namespace Kirki\API\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use WP_Error;
use WP_REST_Controller;


/**
 * FrontendRESTController class
 */
abstract class FrontendRESTController extends WP_REST_Controller {
	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		$this->namespace ='kirki/v1';
		$this->rest_base = 'frontend';
	}


	/**
	 * Permission gate for all frontend endpoints.
	 *
	 * Checks:
	 * 1. A JSON `context` param — validates the user can read the referenced post.
	 * 2. A direct `post_id` param — validates the user can read that post.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		// --- Check 1: context-based permission (post or comment context) ---
		$raw_context = $request->get_param( 'context' );

		if ( $raw_context ) {
			$context = json_decode( $raw_context, true );
			$post_id = $this->extract_post_id_from_context( $context );

			if ( $post_id && ! $this->can_user_read_post( $post_id ) ) {
				return new WP_Error(
					'rest_forbidden',
					'You do not have permission to read this post.',
					array( 'status' => 403 )
				);
			}
		}

		// --- Check 2: direct post_id param ---
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id && ! $this->can_user_read_post( $post_id ) ) {
			return new WP_Error(
				'rest_forbidden',
				'You do not have permission to read this post.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Extracts the relevant post ID from a decoded context array.
	 * - For 'post' context:    uses context['id']
	 * - For 'comment' context: uses context['post_id'] (the parent post)
	 * - For all others:        no post ID to check
	 *
	 * @param mixed $context Decoded JSON context.
	 * @return int|null Sanitized post ID, or null if not applicable.
	 */
	private function extract_post_id_from_context( $context ): ?int {
		if ( ! is_array( $context ) || empty( $context['type'] ) ) {
			return null;
		}

		switch( $context['type'] ) {
			case 'post':
				return isset( $context['id'] )      ? absint( $context['id'] )      : null;
			case 'comment':
				return isset( $context['post_id'] ) ? absint( $context['post_id'] ) : null;
			default:
				return null;
		}
	}

	/**
	 * Determines whether the current user can read the given post.
	 * Publicly published posts are readable by anyone (no login required).
	 * Private/draft/etc. fall back to WordPress capability check.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	protected function can_user_read_post( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$is_password_protected = !empty( $post->post_password );

		if ( 'publish' === $post->post_status && ! $is_password_protected ) {
			return true;
		}

		if ( 'publish' === $post->post_status && $is_password_protected ) {
			return ! post_password_required( $post ) || current_user_can( 'read_post', $post_id );
		}

		return current_user_can( 'read_post', $post_id );
	}
}