<?php

/**
 * Preivew scripts data and styles helper
 *
 * @package kirki
 */

namespace Kirki\Frontend\Preview;

use Kirki\Ajax\Users;
use Kirki\API\ContentManager\ContentManagerHelper;
use Kirki\HelperFunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DataHelper Class
 */
class Utils {

	public static function get_items_data_from_dynamic_contents( $dynamic_content, $options ) {
		$collection_type = $dynamic_content['collectionType'];
		if ( $collection_type === 'users' ) {
			return self::get_users_collection( $dynamic_content, $options );
		} elseif ( $collection_type === 'posts' ) {
			return self::get_posts_collection( $dynamic_content, $options );
		} elseif ( $collection_type === 'terms' ) {
			return self::get_terms_collection( $dynamic_content, $options );
		} else {
			$args       = self::get_common_args( $dynamic_content, $options );
			$collection = apply_filters( 'kirki_collection_' . $dynamic_content['collectionType'], false, $args );
			$data       = $collection ? $collection['data'] : array();
			$pagination = $collection ? $collection['pagination'] : array();
			$item_type  = $collection && isset( $collection['itemType'] ) ? $collection['itemType'] : 'post';
			return array(
				'data'       => $data,
				'pagination' => $pagination,
				'itemType'   => $item_type,
			);
		}
	}

	private static function get_posts_collection( $dynamic_content, $options ) {
		$args       = self::get_common_args( $dynamic_content, $options );
		$collection = HelperFunctions::get_posts( $args );
		$data       = $collection['data'];
		$pagination = $collection['pagination'];
		return array(
			'data'       => $data,
			'pagination' => $pagination,
			'itemType'   => 'post',
		);
	}
	private static function get_terms_collection( $dynamic_content, $options ) {
		$args = array(
			'taxonomy'      => $dynamic_content['taxonomy'],
			'hide_empty'    => false,
			'current_page'  => isset( $options['page'] ) ? $options['page'] : 1,
			'item_per_page' => isset( $dynamic_content['items'] ) ? $dynamic_content['items'] : 3,
			'offset'        => isset( $dynamic_content['offset'] ) ? $dynamic_content['offset'] : 0,
		);

		if ( isset( $dynamic_content['inherit'] ) && $dynamic_content['inherit'] ) {
			if ( isset( $options['itemType'] ) && $options['itemType'] === 'post' ) {
				$post_parent = $options['post']->ID;
			} else {
				$post_parent = HelperFunctions::get_post_id_if_possible_from_url();
			}
			$args['post_parent'] = $post_parent;
			$args['inherit']     = true;
		}

		$terms = HelperFunctions::get_terms( $args );

		$data       = $terms['data'];
		$pagination = $terms['pagination'];
		return array(
			'data'       => $data,
			'pagination' => $pagination,
			'itemType'   => 'term',
		);
	}

	private static function get_users_collection( $dynamic_content, $options ) {
		$args = array();

		if ( isset( $dynamic_content['sorting'], $dynamic_content['sorting']['type'], $dynamic_content['sorting']['value'] ) ) {
			$args['sorting']['order']   = $dynamic_content['sorting']['type'];
			$args['sorting']['orderby'] = $dynamic_content['sorting']['value'];
		}

		if ( isset( $options['filters'] ) ) {
			$args['filters'] = $options['filters'];
		} elseif ( isset( $dynamic_content['filters'] ) ) {
			$args['filters'] = $dynamic_content['filters'];
		}

		if ( isset( $dynamic_content['inherit'] ) && $dynamic_content['inherit'] === true ) {
			$args['inherit']     = true;
			$args['post_parent'] = $options['post']->ID ?? HelperFunctions::get_post_id_if_possible_from_url();
		}

		$item_per_page = isset( $dynamic_content['items'] ) ? $dynamic_content['items'] : 3;
		$offset        = isset( $dynamic_content['offset'] ) ? $dynamic_content['offset'] : 0;

		$current_page = 1;
		if ( isset( $options['page'] ) ) {
			$current_page = $options['page'];
		}

		$args['current_page']  = $current_page;
		$args['item_per_page'] = $item_per_page;
		$args['offset']        = $offset;

		if ( isset( $options['q'] ) ) {
			$args['q'] = $options['q'];
		}

		$users      = Users::get_users( $args );
		$data       = $users['data'];
		$pagination = $users['pagination'];

		return array(
			'data'       => $data,
			'pagination' => $pagination,
			'itemType'   => 'user',
		);
	}


	private static function get_common_args( $dynamic_content, $options ) {
		$args = array();
		if ( isset( $dynamic_content['sorting'], $dynamic_content['sorting']['type'], $dynamic_content['sorting']['value'] ) ) {
			$args['sorting']['order']   = $dynamic_content['sorting']['type'];
			$args['sorting']['orderby'] = $dynamic_content['sorting']['value'];
		}

		if ( isset( $options['filters'] ) ) {
			$args['filters'] = $options['filters'];
		} elseif ( isset( $dynamic_content['filters'] ) ) {
			$args['filters'] = $dynamic_content['filters'];
		}

		if ( isset( $dynamic_content['collectionRandomId'] ) ) {

			// This code is for taxonomy filter START
			$filters    = array();
			$taxonomies = array();
			if ( isset( $_GET['taxonomy'] ) && is_array( $_GET['taxonomy'] ) ) {
				$taxonomies = $_GET['taxonomy'];
			} elseif ( isset( $options['collection_param_filters'], $options['collection_param_filters']['taxonomy'] ) ) {
				// This code came from pagination, filter, search api from frontend
				$taxonomies = $options['collection_param_filters']['taxonomy'];
			}
			foreach ( $taxonomies as $name => $value ) {
				if ( ! empty( $value ) ) {
					$filters[] = array(
						'id'       => 'taxonomy',
						'type'     => 'taxonomy',
						'taxonomy' => $name,
						'terms'    => $value,
					);
				}
			}

			$args['filters'] = array_merge( $args['filters'], $filters );
			// This code is for taxonomy filter END

			// This code is for post/cm filter START
			$ref_post_ids = array();
			if ( isset( $_GET['post'], $_GET['post']['cm_post'] ) && is_array( $_GET['post']['cm_post'] ) ) {
				$ref_post_ids = array_map( 'intval', $_GET['post']['cm_post'] );
			} elseif ( isset( $options['collection_param_filters'], $options['collection_param_filters']['post'], $options['collection_param_filters']['post']['cm_post'] ) ) {
				// This code came from pagination, filter, search api from frontend
				$ref_post_ids = $options['collection_param_filters']['post']['cm_post'];
			}

			if ( ! empty( $ref_post_ids ) ) {

				$included_post_ids = ContentManagerHelper::get_post_ids_by_ref_post_ids( $ref_post_ids );

				$args['IDs'] = $included_post_ids;
				// Make sure all post ids are integers
			}
			// This code is for post/cm filter END
		}

		if ( isset( $dynamic_content['type'] ) ) {
			$args['name'] = $dynamic_content['type'];
		}

		if ( isset( $dynamic_content['inherit'] ) && $dynamic_content['inherit'] === true ) {
			$args['inherit'] = true;

			if ( isset( $options['itemType'], $options[ $options['itemType'] ], $options[ $options['itemType'] ]->ID ) ) {
				// if itemType is set and it has ID property then use it as post_parent
				$args['post_parent'] = $options[ $options['itemType'] ]->ID;
			} else {
				$args['post_parent'] = $options['post']->ID ?? HelperFunctions::get_post_id_if_possible_from_url();
			}

			if ( isset( $options['user'], $options['user']['ID'] ) ) {
				// update $filter data with this user author in property

				$args['context'] = array(
					'id'             => $options['user']['ID'],
					'collectionType' => 'user',
				);
			}
			if ( isset( $options['term'] ) ) {
				// update $filter data with this term in property
				$args['context'] = array(
					'id'             => $options['term']['term_id'],
					'slug'           => $options['term']['slug'],
					'taxonomy'       => $options['term']['taxonomy'],
					'collectionType' => 'term',
				);
			}

			if ( isset( $options['comment'] ) ) {
				$comment = $options['comment'];

				if ( isset( $comment['id'] ) ) {

					$args['context'] = array(
						'comment_ID' => $comment['id'], // comment_ID
					);
				} elseif ( isset( $comment['comment_ID'] ) ) {
					$args['context'] = array(
						'comment_ID' => $comment['comment_ID'], // comment_ID
					);
				}
			}

			// for external plugin support
			if ( isset( $options['itemType'], $options[ $options['itemType'] ] ) ) {
				$args['parent_item_type'] = $options['itemType'];
				$args['parent_item']      = $options[ $options['itemType'] ];
			}
		}
		if ( isset( $dynamic_content['related'] ) && $dynamic_content['related'] === true ) {
			$args['related']             = true;
			$args['related_post_parent'] = $options['post']->ID ?? HelperFunctions::get_post_id_if_possible_from_url();
		}

		if ( ! empty( $dynamic_content['type'] ) && false !== strpos( $dynamic_content['type'], KIRKI_CONTENT_MANAGER_PREFIX ) ) {
			$parent_id           = str_replace( KIRKI_CONTENT_MANAGER_PREFIX . '_', '', $dynamic_content['type'] );
			$args['post_parent'] = $parent_id;
		}

		$item_per_page = isset( $dynamic_content['items'] ) ? $dynamic_content['items'] : 3;
		$offset        = isset( $dynamic_content['offset'] ) ? $dynamic_content['offset'] : 0;

		$current_page = 1;
		if ( isset( $options['page'] ) ) {
			$current_page = $options['page'];
		}

		$args['current_page']  = $current_page;
		$args['item_per_page'] = $item_per_page;
		$args['offset']        = $offset;

		// if options has query value
		if ( isset( $options['q'] ) ) {
			$args['q'] = $options['q'];
		}


		if(empty($args['q'])){
			$args['q'] = HelperFunctions::sanitize_text(isset($_REQUEST['q']) ?$_REQUEST['q']:'' );
		}
		
		if(isset($options['inside_collection']) && $options['inside_collection'] === true){
			// this is for nested collection like: terms, multiref etc
			$args['current_page'] = 1;
			$args['pagination'] = false;
			$args['q'] = '';
		}

		return $args;
	}

	public static function getDynamicRichTextValue( $dynamic_content, $options ) {
		$html = '';
		if ( ! $dynamic_content ) {
			return $html;
		}
		// Post excerpt length for frontend. If post excerpt is used.
		if ( $dynamic_content['value'] === 'post_excerpt' && isset( $dynamic_content['postExcerptLength'] ) ) {
			$post_excerpt_length                  = $dynamic_content['postExcerptLength'] ?? 55;
			$GLOBALS['kirki_post_excerpt_length'] = $post_excerpt_length;
		}

		$contentInfo = array(
			'dynamicContent' => $dynamic_content,
			'options'        => $options,
		);

		if ( isset( $options['itemType'], $options[ $options['itemType'] ], $options[ $options['itemType'] ]->ID ) ) {
			$contentInfo['collectionItem'] = array(
				'ID' => $options[ $options['itemType'] ]->ID,
			);
		}
		$content = apply_filters( 'kirki_dynamic_content', false, $contentInfo );
		if ( $content ) {
			return $content;
		}

		if ( $dynamic_content['type'] === 'post' ) {
			$dynamic_options = array();
			$cm_field_type   = isset( $dynamic_content['cmFieldType'] ) ? $dynamic_content['cmFieldType'] : '';
			if ( 'post_date' === $dynamic_content['value'] && isset( $dynamic_content['format'] ) ) {
				$dynamic_options['format'] = $dynamic_content['format'];
			} elseif ( ( 'post_time' === $dynamic_content['value'] || 'time' === $cm_field_type ) && isset( $dynamic_content['timeFormat'] ) ) {
				$dynamic_options['timeFormat'] = $dynamic_content['timeFormat'];
			}

			$content = HelperFunctions::get_post_dynamic_content(
				$dynamic_content['value'],
				$options['post'] ?? null,
				$dynamic_content['meta'] ?? '',
				$dynamic_options
			);

			$html .= $content;
		} elseif ( $dynamic_content['type'] === 'term' && isset( $options['term'] ) && isset( $options['term'][ $dynamic_content['value'] ] ) ) {
			$html .= $options['term'][ $dynamic_content['value'] ];
		} elseif ( $dynamic_content['type'] === 'user' ) {
			// $html .= $options['user'][ $dynamic_content['value'] ];
			$content = HelperFunctions::get_user_dynamic_content(
				$dynamic_content['value'],
				$options['user']['ID'] ?? null,
				$dynamic_content['meta'] ?? '',
			);

				// Date may need to format
			if ( 'registered_date' === $dynamic_content['value'] && isset( $dynamic_content['format'] ) ) {
				$content = HelperFunctions::format_date( $content, $dynamic_content['format'] );
			}

			$html .= $content;

		} elseif ( $dynamic_content['type'] === 'menu' && isset( $options['menu'] ) && isset( $options['menu']->{$dynamic_content['value']} ) ) {
			$html .= $options['menu']->{$dynamic_content['value']};
		} elseif ( $dynamic_content['type'] === 'others' && isset( $dynamic_content['value'] ) ) {
			// Post count is used in collection item index.
			if ( $dynamic_content['value'] === 'item_index' && isset( $options['item_index'] ) ) {
				$content = $options['item_index'];

				$html .= $content;
			}
		} else {
			$item_type = $dynamic_content['type'];
			if ( isset( $options[ $item_type ] ) ) {
				$data  = $options[ $item_type ] ?? array();
				$value = isset( $data[ $dynamic_content['value'] ] ) ? $data[ $dynamic_content['value'] ] : '';
				$html .= $value;
			}
		}

		return $html;
	}
}
