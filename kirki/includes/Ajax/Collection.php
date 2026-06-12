<?php
/**
 * Dynamic content/Collection API
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;

/**
 * Collection API Class
 */
class Collection {

	/**
	 * Get collection return api response
	 *
	 * @return void wpjson response
	 */
	public static function get_collection() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$collection_type = HelperFunctions::sanitize_text( isset( $_GET['collectionType'] ) ? $_GET['collectionType'] : null );
		$name            = HelperFunctions::sanitize_text( isset( $_GET['name'] ) ? $_GET['name'] : null );
		$taxonomy        = HelperFunctions::sanitize_text( isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : null );
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sorting_param = HelperFunctions::sanitize_text( isset( $_GET['sorting'] ) ? $_GET['sorting'] : null );
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$filter_param        = HelperFunctions::sanitize_text( isset( $_GET['filters'] ) ? $_GET['filters'] : null );
		$inherit             = HelperFunctions::sanitize_text( $_GET['inherit'] ?? false );
		$related             = HelperFunctions::sanitize_text( $_GET['related'] ?? false );
		$post_parent         = HelperFunctions::sanitize_text( $_GET['post_parent'] ?? null );
		$related_post_parent = HelperFunctions::sanitize_text( $_GET['related_post_parent'] ?? false );
		$item_per_page       = HelperFunctions::sanitize_text( $_GET['items'] ?? 3 );
		$current_page        = HelperFunctions::sanitize_text( $_GET['current_page'] ?? 1 );
		$offset              = HelperFunctions::sanitize_text( $_GET['offset'] ?? 0 );
		$context_param       = HelperFunctions::sanitize_text( isset( $_GET['context'] ) ? $_GET['context'] : null );
		$query               = HelperFunctions::sanitize_text( isset( $_GET['q'] ) ? $_GET['q'] : '' );

		$data = self::resolve_collection(
			$collection_type,
			$name,
			$taxonomy,
			$sorting_param,
			$filter_param,
			$inherit,
			$related,
			$post_parent,
			$related_post_parent,
			$item_per_page,
			$current_page,
			$offset,
			$context_param,
			$query
		);

		wp_send_json( $data );
	}

	/**
	 * Resolve collection data.
	 *
	 * @return mixed
	 */
	private static function resolve_collection(
		$collection_type,
		$name,
		$taxonomy,
		$sorting_param,
		$filter_param,
		$inherit,
		$related,
		$post_parent,
		$related_post_parent,
		$item_per_page,
		$current_page,
		$offset,
		$context_param,
		$query
	) {
		$sorting = null;
		$filters = null;
		$context = null;

		// handle bool
		$inherit = $inherit === 'true' ? true : false;
		$related = $related === 'true' ? true : false;

		if ( isset( $sorting_param ) ) {
			$sorting = $sorting_param;
		}

		if ( isset( $filter_param ) ) {
			$filters = $filter_param;
		}

		if ( isset( $context_param ) ) {
			$context = $context_param;
		}

		$args = array(
			'name'                => $name,
			'sorting'             => $sorting,
			'filters'             => $filters,
			'inherit'             => $inherit,
			'post_parent'         => $post_parent,
			'post_status'         => 'any',
			'related'             => $related,
			'related_post_parent' => $related_post_parent,
			'item_per_page'       => $item_per_page,
			'current_page'        => $current_page,
			'offset'              => $offset,
			'context'             => $context,
			'q'                   => $query,
		);

		if ( $collection_type === 'posts' ) {
			return HelperFunctions::get_posts( $args );
		} elseif ( $collection_type === 'users' ) {
			return Users::get_users( $args );
		} elseif ( $collection_type === 'terms' ) {
			$args  = array(
				'taxonomy'      => $taxonomy,
				'hide_empty'    => false,
				'inherit'       => $inherit,
				'post_parent'   => $post_parent,
				'item_per_page' => $item_per_page,
				'current_page'  => $current_page,
				'offset'        => $offset,
			);
			return HelperFunctions::get_terms( $args );
		} else {
			return apply_filters( 'kirki_collection_' . $collection_type, false, $args );
		}
	}

	/**
	 * Get batched collection data.
	 *
	 * @return void wpjson response
	 */
	public static function get_collection_batch() {         //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$items = HelperFunctions::sanitize_text( isset( $_POST['items'] ) ? $_POST['items'] : null );
		$items = json_decode( stripslashes( $items ), true );

		if ( ! is_array( $items ) ) {
			wp_send_json( array() );
		}

		$results = array();

		foreach ( $items as $item ) {
			$id      = isset( $item['id'] ) ? $item['id'] : '';
			$payload = isset( $item['payload'] ) ? $item['payload'] : array();

			$data = self::resolve_collection(
				isset( $payload['collectionType'] ) ? $payload['collectionType'] : null,
				isset( $payload['name'] ) ? $payload['name'] : null,
				isset( $payload['taxonomy'] ) ? $payload['taxonomy'] : null,
				isset( $payload['sorting'] ) ? $payload['sorting'] : null,
				isset( $payload['filters'] ) ? $payload['filters'] : null,
				isset( $payload['inherit'] ) ? $payload['inherit'] : false,
				isset( $payload['related'] ) ? $payload['related'] : false,
				isset( $payload['post_parent'] ) ? $payload['post_parent'] : null,
				isset( $payload['related_post_parent'] ) ? $payload['related_post_parent'] : false,
				isset( $payload['items'] ) ? $payload['items'] : 3,
				isset( $payload['current_page'] ) ? $payload['current_page'] : 1,
				isset( $payload['offset'] ) ? $payload['offset'] : 0,
				isset( $payload['context'] ) ? $payload['context'] : null,
				isset( $payload['q'] ) ? $payload['q'] : ''
			);

			$results[] = array(
				'id'   => $id,
				'data' => $data,
			);
		}

		wp_send_json( $results );
	}

	public static function get_external_collection_options() {
		$collection_type = HelperFunctions::sanitize_text( isset( $_GET['collectionType'] ) ? $_GET['collectionType'] : null );
		$type            = HelperFunctions::sanitize_text( isset( $_GET['type'] ) ? $_GET['type'] : null );

		$args = array(
			'type'           => $type,
			'collectionType' => $collection_type,
		);

		$collection = array();
		$collection = apply_filters( 'kirki_external_collection_options', $collection, $args );

		wp_send_json( $collection );
	}

	public static function get_external_collection_item_type() {
		$type      = HelperFunctions::sanitize_text( isset( $_GET['type'] ) ? $_GET['type'] : null );
		$item_type = 'post';
		$item_type = apply_filters( 'kirki_external_collection_item_type', $item_type, $type );
		wp_send_json( $item_type );
	}
}
