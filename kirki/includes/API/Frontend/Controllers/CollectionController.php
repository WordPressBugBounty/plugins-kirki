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

use Kirki\Ajax\Symbol;
use Kirki\Ajax\Users;
use Kirki\Frontend\Preview\Preview;
use Kirki\HelperFunctions;
use WP_REST_Server;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Resources\PageContentResource;
use Kirki\App\Supports\Facades\Page;
use Kirki\Frontend\Preview\DataHelper;

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

	private function prepare_kirki_data_for_collection($kirki_data){
		$data_n_styles = [];
		$post_id = (int) $kirki_data['post_id'];
		$collection_data_id = $kirki_data['collection_data_id'];
		$post = get_post($post_id);
		if($post){
			if($post->post_type === 'kirki_symbol'){
				$symbol = Symbol::get_single_symbol( $post_id, true, false, array() );

				if ( $symbol ) {
					$symbol_data = $symbol['symbolData'];
					if ( $symbol_data && isset( $symbol_data['data'] ) ) {
						$all_blocks = get_post_meta($post_id, 'kirki', true);
						$all_styles = Page::get_page_styleblocks($post_id, false);
						DataHelper::get_data_and_styles_from_root( $collection_data_id, $data_n_styles, $symbol_data['data'], $symbol_data['styleBlocks'] );
					}
				}
			}else {
				$all_blocks = get_post_meta($post_id, 'kirki', true);
				$all_styles = Page::get_page_styleblocks($post_id, false);
				DataHelper::get_data_and_styles_from_root( $collection_data_id, $data_n_styles, $all_blocks['blocks'], $all_styles );
			}
		}
		return $data_n_styles;
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
		// $collection_id            = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$collection_param_filters = json_decode( $request->get_param( 'filters' ), true );
		$kirki_data               = json_decode( $request->get_param( 'kirki_data' ), true );
		$context                  = json_decode( $request->get_param( 'context' ), true );
		$query                    = HelperFunctions::sanitize_text( $request->get_param( 'q' ) );

		$collection_param_filters = is_array( $collection_param_filters ) ? $collection_param_filters : array();
		$kirki_data               = is_array( $kirki_data ) ? $kirki_data : array();
		$context                  = is_array( $context ) ? $context : array();

		$collection_id            = $kirki_data['collection_data_id'] ?? false;
		$data_n_styles               = $this->prepare_kirki_data_for_collection($kirki_data);

		$blocks = $data_n_styles['blocks'] ?? array();
		$styles = $data_n_styles['styles'] ?? array();

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
			'search_related_collection_ids' => [$collection_id => true],
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

		// $collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );
		$kirki_data    = is_array( $kirki_data ) ? $kirki_data : array();

		$collection_id            = $kirki_data['collection_data_id'] ?? false;
		$data_n_styles = $this->prepare_kirki_data_for_collection($kirki_data);

		$blocks = $data_n_styles['blocks'] ?? array();
		$styles = $data_n_styles['styles'] ?? array();

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
		// $collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );
		$query         = HelperFunctions::sanitize_text( $request->get_param( 'q' ) );
		$kirki_data    = is_array( $kirki_data ) ? $kirki_data : array();
		
		$collection_id            = $kirki_data['collection_data_id'] ?? false;
		$data_n_styles = $this->prepare_kirki_data_for_collection($kirki_data);

		$blocks = $data_n_styles['blocks'] ?? array();
		$styles = $data_n_styles['styles'] ?? array();

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
	 *
	 * @param \WP_REST_Request $request all user request parameter.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_terms( $request ) {
		$page = absint( $request->get_param( 'page' ) );
		$page = empty( $page ) ? 1 : $page;

		// $collection_id = HelperFunctions::sanitize_text( $request->get_param( 'collection_id' ) );
		$post_id       = HelperFunctions::sanitize_text( $request->get_param( 'post_id' ) );
		$kirki_data    = json_decode( $request->get_param( 'kirki_data' ), true );
		$kirki_data    = is_array( $kirki_data ) ? $kirki_data : array();

		$collection_id            = $kirki_data['collection_data_id'] ?? false;
		$data_n_styles = $this->prepare_kirki_data_for_collection($kirki_data);

		$blocks = $data_n_styles['blocks'] ?? array();
		$styles = $data_n_styles['styles'] ?? array();

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
