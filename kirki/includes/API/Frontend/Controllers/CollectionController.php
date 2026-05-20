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

use Kirki\Ajax\Users;
use Kirki\Frontend\Preview\Preview;
use Kirki\HelperFunctions;
use WP_REST_Server;


/**
 * CollectionController
 * Collection related routes and controller functions
 */
class CollectionController extends FrontendRESTController {

	/**
	 * Register register
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/collection',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_collection' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/comments',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_comments' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/users',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_users' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/terms',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_terms' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Creates a collection page
	 *
	 * @param \WP_REST_Request $request all user request parameter.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_collection( $request ) {
		$page                     = absint( $request->get_param( 'page' ) );
		$page                     = empty( $page ) ? 1 : $page;
		$collection_id            = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$collection_param_filters = json_decode( $request->get_param( 'filters' ), true );
		$kirki_data               = json_decode( $request->get_param( 'kirki_data' ), true );
		$context                  = json_decode( $request->get_param( 'context' ), true );
		$query                    = HelperFunctions::sanitize_text( $request->get_param( 'q' ) );

		$collection_param_filters = is_array( $collection_param_filters ) ? $collection_param_filters : array();
		$kirki_data               = is_array( $kirki_data ) ? $kirki_data : array();
		$context                  = is_array( $context ) ? $context : array();

		$blocks = $kirki_data['blocks'] ?? array();
		$styles = $kirki_data['styles'] ?? array();

		$options = array();
		if ( $context ) {
			if ( isset( $context['type'] ) && $context['type'] === 'post' ) {
				$options = array(
					'post' => get_post( absint( $context['id'] ?? 0 ) ),
				);
			} elseif ( isset( $context['type'] ) && $context['type'] === 'user' ) {
				$options = array(
					'user' => Users::get_user_by_id( absint( $context['id'] ?? 0 ) ),
				);
			} elseif ( isset( $context['type'] ) && $context['type'] === 'term' ) {
				$options = array(
					'term' => get_term( absint( $context['id'] ?? 0 ) )->to_array(),
				);
			} elseif ( isset( $context['type'] ) && $context['type'] === 'comment' ) {
				$options = array(
					'post'    => get_post( absint( $context['post_id'] ?? 0 ) ),
					'comment' => get_comment( absint( $context['comment']['comment_ID'] ?? 0 ) ),
				);
			}
		}

		$params = array(
			'blocks'       => $blocks,
			'style_blocks' => $styles,
			'root'         => $collection_id,
			'post_id'      => null,
			'options'      => array_merge(
				array(
					'page'                     => $page,
					'collection_param_filters' => $collection_param_filters,
					'q'                        => $query,
				),
				$options,
				$context
			),
		);

		$collection_wrapper_html_string = HelperFunctions::get_html_using_preview_script( $params );

		return rest_ensure_response( $collection_wrapper_html_string );
	}

	/**
	 * Creates a collection page
	 *
	 * @param \WP_REST_Request $request all user request parameter.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_comments( $request ) {
		$page = absint( $request->get_param( 'page' ) );
		$page = empty( $page ) ? 1 : $page;

		$collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );

		$blocks = $kirki_data['blocks'];
		$styles = $kirki_data['styles'];

		$params = array(
			'blocks'       => $blocks,
			'style_blocks' => $styles,
			'root'         => $collection_id,
			'post_id'      => null,
			'options'      => array(
				'page' => $page,
				'post' => get_post( $post_id ),
			),
		);

		$collection_wrapper_html_string = HelperFunctions::get_html_using_preview_script( $params );
		return rest_ensure_response( $collection_wrapper_html_string );
	}


	/**
	 * Creates a collection page
	 *
	 * @param \WP_REST_Request $request all user request parameter.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_users( $request ) {
		$page          = absint( $request->get_param( 'page' ) );
		$page          = empty( $page ) ? 1 : $page;
		$collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );
		$query         = HelperFunctions::sanitize_text( $request->get_param( 'q' ) );

		$blocks = $kirki_data['blocks'];
		$styles = $kirki_data['styles'];

		$params = array(
			'blocks'       => $blocks,
			'style_blocks' => $styles,
			'root'         => $collection_id,
			'post_id'      => null,
			'options'      => array(
				'post' => get_post( $post_id ),
				'page' => $page,
				'q'    => $query,
			),
		);

		$collection_wrapper_html_string = HelperFunctions::get_html_using_preview_script( $params );

		return rest_ensure_response( $collection_wrapper_html_string );
	}

	/**
	 * Gets terms of a collection
	 */

	public function get_terms( $request ) {
		$page = absint( $request->get_param( 'page' ) );
		$page = empty( $page ) ? 1 : $page;

		$collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );

		$blocks = $kirki_data['blocks'];
		$styles = $kirki_data['styles'];

		$params = array(
			'blocks'       => $blocks,
			'style_blocks' => $styles,
			'root'         => $collection_id,
			'post_id'      => null,
			'options'      => array(
				'page' => $page,
				'post' => get_post( $post_id ),
			),
		);

		$collection_wrapper_html_string = HelperFunctions::get_html_using_preview_script( $params );
		return rest_ensure_response( $collection_wrapper_html_string );
	}
}
