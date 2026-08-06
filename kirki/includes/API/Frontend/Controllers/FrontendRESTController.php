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
		// --- Check 1: context-based permission ---
		$raw_context = $request->get_param( 'context' );

		if ( $raw_context ) {
			$context = json_decode( $raw_context, true );

			$context_type = $context['type'];

			// For user contexts, require list_users capability.
			if ( 'user' === $context_type ) {
				$target_user_id = absint( $context['id'] ?? 0 );
				// Allow access only if the requester is querying their own record OR list_users capability
				if ( $target_user_id !== get_current_user_id() && ! current_user_can( 'list_users' ) ) {
					return new WP_Error(
						'rest_forbidden',
						'You do not have permission to read this user.',
						array( 'status' => 403 )
					);
				}
			} elseif ( 'term' === $context_type ) {
				// Term contexts are publicly accessible (terms are public by default).
			} elseif ( 'post' === $context_type || 'comment' === $context_type ) {
				$post_id = $this->extract_post_id_from_context( $context );
				if ( $post_id && ! $this->can_user_read_post( $post_id ) ) {
					return new WP_Error(
						'rest_forbidden',
						'You do not have permission to read this post.',
						array( 'status' => 403 )
					);
				}
			} else {
				return new WP_Error(
					'rest_forbidden',
					'Unsupported context type.',
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
	 * Only handles 'post' and 'comment' types; all other types return null
	 * because they do not reference a post that requires a read-access check.
	 * NOTE: Callers MUST perform their own capability checks for non-post
	 * context types (user, term, etc.) before reaching this method.
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