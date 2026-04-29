<?php

/**
 * DynamicContent manager API
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\API\ContentManager\ContentManagerHelper;
use Kirki\HelperFunctions;

use function PHPSTORM_META\map;

/**
 * DynamicContent API Class
 */
class DynamicContent {


	/**
	 * Get Dynamic element data
	 *
	 * @return void wpjson response
	 */
	public static function get_dynamic_element_data() {         //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// $content_type = HelperFunctions::sanitize_text( isset( $_GET['content_type'] ) ? $_GET['content_type'] : null );
		// //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// $content_value = HelperFunctions::sanitize_text( isset( $_GET['content_value'] ) ? $_GET['content_value'] : null );
		// //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// $meta_name = HelperFunctions::sanitize_text( isset( $_GET['meta_name'] ) ? $_GET['meta_name'] : null );
		// //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// $post_id = (int) HelperFunctions::sanitize_text( isset( $_GET['post_id'] ) ? $_GET['post_id'] : null );
		// $settings = HelperFunctions::sanitize_text( isset( $_GET['settings'] ) ? $_GET['settings'] : null );

		$content_info = HelperFunctions::sanitize_text( isset( $_GET['contentInfo'] ) ? $_GET['contentInfo'] : null );

		$content_info = json_decode( stripslashes( $content_info ), true );

		$content = apply_filters( 'kirki_dynamic_content', false, $content_info );
		if ( $content !== false ) {
			wp_send_json( $content );
		}

		$dynamic_content = isset( $content_info['dynamicContent'] ) ? $content_info['dynamicContent'] : array();
		$content_type    = isset( $dynamic_content['type'] ) ? $dynamic_content['type'] : 'post';
		$cm_field_type   = isset( $dynamic_content['cmFieldType'] ) ? $dynamic_content['cmFieldType'] : '';
		$time_format     = isset( $dynamic_content['timeFormat'] ) ? $dynamic_content['timeFormat'] : 'h:i a';
		$date_format     = isset( $dynamic_content['format'] ) ? $dynamic_content['format'] : 'MM-DD-YYYY';
		$content_value   = isset( $dynamic_content['value'] ) ? $dynamic_content['value'] : 'post_title';
		$meta_name       = isset( $dynamic_content['meta'] ) ? $dynamic_content['meta'] : '';
		$post_id         = (int) HelperFunctions::sanitize_text( isset( $content_info['post_id'] ) ? $content_info['post_id'] : null );

		// Post excerpt length for kirki editor
		if ( ! empty( $content_value ) && $content_value === 'post_excerpt' && isset( $dynamic_content['postExcerptLength'] ) ) {
			$GLOBALS['kirki_post_excerpt_length'] = (int) $dynamic_content['postExcerptLength'];
		}

		switch ( $content_type ) {
			case 'post': {
					$post = null;

				if ( ! empty( $post_id ) ) {
					$post = get_post( $post_id );
				} elseif ( isset( $content_info['collectionItem'], $content_info['collectionItem']['ID'] ) ) {
					$post = get_post( $content_info['collectionItem']['ID'] );
				}

					$dynamic_options = array();

				if ( $cm_field_type === 'time' || $content_value === 'post_time' ) {
					$dynamic_options['timeFormat'] = $time_format;
				}
				if ( $cm_field_type === 'date' || $content_value === 'post_date' ) {
					$dynamic_options['format'] = $date_format;
				}

					$content = HelperFunctions::get_post_dynamic_content( $content_value, $post, $meta_name, $dynamic_options );

					wp_send_json( $content );
				break;
			}

			case 'author': {
					$post = null;

				if ( ! empty( $post_id ) ) {
					$post = get_post( $post_id );
				} elseif ( isset( $content_info['collectionItem'], $content_info['collectionItem']['ID'] ) ) {
					$post = get_post( $content_info['collectionItem']['ID'] );
				}
					$content = HelperFunctions::get_post_dynamic_content( $content_value, $post );
					wp_send_json( $content );
				break;
			}

			case 'user': {
					$user_id = get_current_user_id();
				if ( isset( $content_info['collectionItem'], $content_info['collectionItem']['ID'] ) ) {
					$user_id = $content_info['collectionItem']['ID'];
				}

					$dynamic_options = array();

				if ( $content_value === 'registered_date' ) {
					$dynamic_options['format'] = $date_format;
				}

					$content = HelperFunctions::get_user_dynamic_content( $content_value, $user_id, $meta_name, $dynamic_options );
					wp_send_json( $content );
				break;
			}

			case 'site': {
					$content = HelperFunctions::get_post_dynamic_content( $content_value );
					wp_send_json( $content );
					break;
			}

			case 'term': {
					$term_id = $post_id; // here post_id mean term_id based on content_type, here set term_id;
					$content = HelperFunctions::get_term_dynamic_content( $content_value, $term_id, $meta_name );
					wp_send_json( $content );
					break;
			}

			default: {
					wp_send_json( false );
					break;
			}
		}
	}


	public static function get_default_condition_data( $post ) {
		$post_fields = array(
			array(
				'value'         => 'post_id',
				'title'         => 'Post ID',
				'operand'       => array(
					'type' => 'search',
					'item' => 'post',
				),
				'operator_type' => 'dropdown_operators',
			),
			array(
				'value'         => 'post_title',
				'title'         => 'Post Title',
				'operand'       => null,
				'operator_type' => 'text_operators',
			),
			array(
				'value'         => 'post_author',
				'title'         => 'Post Author',
				'operand'       => array(
					'type' => 'search',
					'item' => 'user',
				),
				'operator_type' => 'dropdown_operators',
			),
			array(
				'value'         => 'post_date',
				'title'         => 'Post Date',
				'operand'       => array( 'type' => 'date' ),
				'operator_type' => 'date_operators',
			),
			array(
				'value'         => 'post_time',
				'title'         => 'Post Time',
				'operand'       => array( 'type' => 'time' ),
				'operator_type' => 'text_operators',
			),
		);

		$user_fields = array(
			array(
				'value'         => 'display_name',
				'title'         => 'Display Name',
				'operand'       => null,
				'operator_type' => 'text_operators',
			),
			array(
				'value'         => 'user_email',
				'title'         => 'User Email',
				'operand'       => null,
				'operator_type' => 'text_operators',
			),
			array(
				'value'         => 'user_nicename',
				'title'         => 'User Nice Name',
				'operand'       => null,
				'operator_type' => 'text_operators',
			),
			array(
				'value'         => 'registered_date',
				'title'         => 'Registered Date',
				'operand'       => array( 'type' => 'date' ),
				'operator_type' => 'date_operators',
			),
			array(
				'value'         => 'registered_time',
				'title'         => 'Registered Time',
				'operand'       => array( 'type' => 'time' ),
				'operator_type' => 'text_operators',
			),
		);

		// Add post fields
		foreach ( $post_fields as $field ) {
			$post['post'][ $field['value'] ] = array(
				'title'         => $field['title'],
				'operator_type' => $field['operator_type'],
				'operand'       => $field['operand'],
			);
		}

		// Add user fields
		foreach ( $user_fields as $field ) {
			$post['user'][ $field['value'] ] = array(
				'title'         => $field['title'],
				'operator_type' => $field['operator_type'],
				'operand'       => $field['operand'],
			);
		}

		return $post;
	}


	public static function get_visibility_condition_fields() {
		$conditions      = array();
		$collection_data = HelperFunctions::sanitize_text( isset( $_GET['collectionData'] ) ? $_GET['collectionData'] : false );
		if ( $collection_data ) {
			$collection_data = json_decode( $collection_data, true );
		}

		$type            = $collection_data['type'];
		$collection_type = $collection_data['collectionType'];

		$roles   = array_map(
			fn( $key, $value) => array(
				'value' => $key,
				'title' => $value,
			),
			array_keys( wp_roles()->role_names ),
			wp_roles()->role_names
		);
		$roles[] = array(
			'value' => 'logged_in',
			'title' => 'Logged In',
		);
		$roles[] = array(
			'value' => 'guest',
			'title' => 'Guest',
		);

		$conditions['user'] = array(
			'title'  => 'User',
			'fields' => array(
				array(
					'value'         => 'display_name',
					'title'         => 'Display Name',
					'operator_type' => 'text_operators',
				),
				array(
					'value'         => 'user_login',
					'title'         => 'Username',
					'operator_type' => 'text_operators',
				),
				array(
					'value'         => 'user_nicename',
					'title'         => 'Nice Name',
					'operator_type' => 'text_operators',
				),
				array(
					'value'         => 'user_email',
					'title'         => 'Email',
					'operator_type' => 'text_operators',
				),
				array(
					'value'         => 'user_registered',
					'title'         => 'Registered Date',
					'operator_type' => 'date_operators',
					'operand_type'  => KIRKI_PLUGIN_SETTINGS['DATEPICKER'],
				),
				array(
					'value'         => 'role',
					'title'         => 'Role',
					'operator_type' => 'list_operators',
					'operand_type'  => array_merge(
						KIRKI_PLUGIN_SETTINGS['SELECT'],
						array(
							'options' => $roles,
						)
					),
				),
			),
		);

		if ( $collection_type === 'posts' ) {
			$conditions['post']           = array(
				'title'  => 'Post',
				'fields' => array(),
			);
			$conditions['post']['fields'] = array(
				array(
					'value'         => 'post_id',
					'title'         => 'ID',
					'operator_type' => 'dropdown_operators',
				),
				array(
					'value'         => 'post_title',
					'title'         => 'Title',
					'operator_type' => 'text_operators',
				),
				array(
					'value'         => 'post_author',
					'title'         => 'Author',
					'operator_type' => 'dropdown_operators',
					'operand_type'  => array_merge(
						KIRKI_PLUGIN_SETTINGS['SELECT'],
						array(
							'options' => array_map(
								fn( $a) => array(
									'value' => $a->data->ID,
									'title' => $a->data->display_name,
								),
								get_users()
							),
						)
					),
				),
				array(
					'value'         => 'post_date',
					'title'         => 'Date',
					'operator_type' => 'date_operators',
					'operand_type'  => KIRKI_PLUGIN_SETTINGS['DATEPICKER'],
				),
			);
		}
		$conditions = apply_filters( 'kirki_visibility_condition_fields', $conditions, $collection_data );

		wp_send_json( $conditions );
	}



	public static function getCustomFields() {
		return array(
			array(
				'type'               => 'text',
				'dynamicContentType' => 'content',
			),
			array(
				'type'               => 'rich-text',
				'dynamicContentType' => 'content',
			),
			array(
				'type'               => 'image',
				'dynamicContentType' => 'image',
			),
			array(
				'type'               => 'video',
				'dynamicContentType' => 'video',
			),
			array(
				'type'               => 'email',
				'dynamicContentType' => array( 'content', 'anchor' ),
			),
			array(
				'type'               => 'phone',
				'dynamicContentType' => array( 'content', 'anchor' ),
			),
			array(
				'type'               => 'number',
				'dynamicContentType' => 'content',
			),
			array(
				'type'               => 'date',
				'dynamicContentType' => 'content',
			),
			array(
				'type'               => 'time',
				'dynamicContentType' => 'content',
			),
			array( 'type' => 'switch' ), // no dynamicContentType
			array(
				'type'               => 'option',
				'dynamicContentType' => array( 'content', 'anchor' ),
			),
			array(
				'type'               => 'url',
				'dynamicContentType' => array( 'content', 'anchor' ),
			),
			array(
				'type'               => 'file',
				'dynamicContentType' => array( 'anchor' ),
			),
			array( 'type' => 'reference' ), // no dynamicContentType
			array( 'type' => 'multi-reference' ), // no dynamicContentType
			array(
				'type'               => 'gallery',
				'dynamicContentType' => 'gallery',
			),
		);
	}

	public static function sortContentManagerCustomFieldsByDynamicContentType() {
		$custom_fields = self::getCustomFields();
		$sorted_data   = array(
			'content' => array(),
			'image'   => array(),
			'video'   => array(),
			'anchor'  => array(),
			'gallery' => array(),
		);

		foreach ( $custom_fields as $field ) {
			if ( ! isset( $field['dynamicContentType'] ) ) {
				continue;
			}

			if ( is_array( $field['dynamicContentType'] ) ) {
				foreach ( $field['dynamicContentType'] as $type ) {
					if ( array_key_exists( $type, $sorted_data ) ) {
						$sorted_data[ $type ][] = $field['type'];
					}
				}
			} elseif ( is_string( $field['dynamicContentType'] ) ) {
				$type = $field['dynamicContentType'];
				if ( array_key_exists( $type, $sorted_data ) ) {
					$sorted_data[ $type ][] = $field['type'];
				}
			}
		}

		return $sorted_data;
	}



	public static function convertKirkiCMSFieldsToSelectOptions( $type, $fields ) {
		if ( ! is_array( $fields ) ) {
			return array();
		}

		$sorted_cms_fields = self::sortContentManagerCustomFieldsByDynamicContentType();

		if ( ! isset( $sorted_cms_fields[ $type ] ) ) {
			return array();
		}

		$current_type_cms_fields = $sorted_cms_fields[ $type ];

		$options = array();

		foreach ( $fields as $field ) {
			if ( in_array( $field['type'], $current_type_cms_fields ) ) {
				$options[] = array(
					'title' => isset( $field['title'] ) ? $field['title'] : '',
					'value' => isset( $field['id'] ) ? $field['id'] : '',
					'type'  => isset( $field['type'] ) ? $field['type'] : '',
				);
			}
		}

		return $options;
	}


	public static function get_default_dynamic_content_fields() {
		return array(
			'type'           => array(
				'content' => array(
					'id' => 'content',
				),
				'image'   => array(
					'id' => 'image',
				),
				'video'   => array(
					'id' => 'video',
				),
				'anchor'  => array(
					'id' => 'anchor',
				),
			),
			'typeValues'     => array(
				'content' => array(
					array(
						'value' => 'manual',
						'title' => 'Manual',
					),
					array(
						'value' => 'post',
						'title' => 'Post',
					),
					array(
						'value' => 'term',
						'title' => 'Term',
					),
					array(
						'value' => 'comment',
						'title' => 'Comment',
					),
					array(
						'value' => 'user',
						'title' => 'User',
					),
					array(
						'value' => 'site',
						'title' => 'Site',
					),
					array(
						'value' => 'others',
						'title' => 'Others',
					),
				),
				'image'   => array(
					array(
						'value' => 'manual',
						'title' => 'Manual',
					),
					array(
						'value' => 'post',
						'title' => 'Post',
					),
					array(
						'value' => 'site',
						'title' => 'Site',
					),
					array(
						'value' => 'author',
						'title' => 'Author',
					),
					array(
						'value' => 'user',
						'title' => 'User',
					),
				),
				'video'   => array(
					array(
						'value' => 'manual',
						'title' => 'Manual',
					),
					array(
						'value' => 'post',
						'title' => 'Post',
					),
				),
				'anchor'  => array(
					array(
						'value' => 'manual',
						'title' => 'Manual',
					),
					array(
						'value' => 'post',
						'title' => 'Post',
					),
					array(
						'value' => 'term',
						'title' => 'Term',
					),
					array(
						'value' => 'author',
						'title' => 'Author',
					),
					array(
						'value' => 'user',
						'title' => 'User',
					),
				),
			),
			'typeValuesAttr' => array(
				'content' => array(
					'manual'  => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
					'post'    => array(
						array(
							'value' => 'post_id',
							'title' => 'ID',
						),
						array(
							'value' => 'post_title',
							'title' => 'Title',
						),
						array(
							'value' => 'post_author',
							'title' => 'Author',
						),
						array(
							'value' => 'post_date',
							'title' => 'Date',
						),
						array(
							'value' => 'post_time',
							'title' => 'Time',
						),
						array(
							'value' => 'post_content',
							'title' => 'Content',
						),
						array(
							'value' => 'post_excerpt',
							'title' => 'Excerpt',
						),
						array(
							'value' => 'post_meta',
							'title' => 'Meta',
						),
					),
					'comment' => array(
						array(
							'value' => 'comment_id',
							'title' => 'ID',
						),
						array(
							'value' => 'parent_id',
							'title' => 'Parent ID',
						),
						array(
							'value' => 'post_id',
							'title' => 'Post ID',
						),
						array(
							'value' => 'comment_author',
							'title' => 'Author',
						),
						array(
							'value' => 'comment_author_email',
							'title' => 'Author email',
						),
						array(
							'value' => 'comment_date',
							'title' => 'Date',
						),
						array(
							'value' => 'comment_time',
							'title' => 'Time',
						),
						array(
							'value' => 'comment_content',
							'title' => 'Content',
						),
						array(
							'value' => 'comment_karma',
							'title' => 'Karma',
						),
					),
					'term'    => array(
						array(
							'value' => 'name',
							'title' => 'Name',
						),
						array(
							'value' => 'slug',
							'title' => 'Slug',
						),
						array(
							'value' => 'description',
							'title' => 'Description',
						),
						array(
							'value' => 'count',
							'title' => 'Count',
						),
					),
					'page'    => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
					'user'    => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'display_name',
							'title' => 'Display Name',
						),
						array(
							'value' => 'user_email',
							'title' => 'Email',
						),
						array(
							'value' => 'user_nicename',
							'title' => 'Nice Name',
						),
						array(
							'value' => 'registered_date',
							'title' => 'Registered Date',
						),
						array(
							'value' => 'registered_time',
							'title' => 'Registered Time',
						),
						array(
							'value' => 'initials',
							'title' => 'Initials',
						),
						array(
							'value' => 'user_meta',
							'title' => 'Meta',
						),
					),
					'site'    => array(
						array(
							'value' => 'site_name',
							'title' => 'Site Name',
						),
						array(
							'value' => 'site_description',
							'title' => 'Site Description',
						),
						array(
							'value' => 'site_url',
							'title' => 'Site URL',
						),
					),
					'others'  => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'item_index',
							'title' => 'Item Index',
						),
					),
				),
				'image'   => array(
					'manual' => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
					'post'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'featured_image',
							'title' => 'Featured Image',
						),
						array(
							'value' => 'post_meta',
							'title' => 'Post Meta',
						),
					),
					'site'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'site_logo',
							'title' => 'Site Logo',
						),
					),
					'author' => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'author_profile_picture',
							'title' => 'Author Profile Picture',
						),
					),
					'user'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'profile_image',
							'title' => 'Profile Image',
						),
					),
				),
				'video'   => array(
					'manual' => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
					'post'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
				),
				'anchor'  => array(
					'manual' => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
					),
					'post'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'post_page_link',
							'title' => 'Post link',
						),
					),
					'term'   => array(
						array(
							'value' => 'link',
							'title' => 'Link',
						),
					),
					'author' => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'author_posts_page_link',
							'title' => 'Author Posts',
						),
					),
					'user'   => array(
						array(
							'value' => 'none',
							'title' => 'None',
						),
						array(
							'value' => 'user_url',
							'title' => 'User URL',
						),
					),
				),
			),
		);
	}

	public static function get_dynamic_content_fields() {
		$data = self::get_default_dynamic_content_fields();

		$collection_data = HelperFunctions::sanitize_text( isset( $_GET['collectionData'] ) ? $_GET['collectionData'] : false );
		if ( $collection_data ) {
			$collection_data = json_decode( $collection_data, true );
		}
		$type                 = $collection_data['type'];
		$collection_type      = $collection_data['collectionType'];
		$element_content_type = $collection_data['elementContentType'] ?? '';

		$clean_data                                        = array();
		$clean_data['type'][ $element_content_type ]       = $data['type'][ $element_content_type ];
		$clean_data['typeValues'][ $element_content_type ] = $data['typeValues'][ $element_content_type ];
		$clean_data['typeValuesAttr'][ $element_content_type ] = $data['typeValuesAttr'][ $element_content_type ];
		$data = $clean_data;

		if ( $collection_type === 'posts' && str_contains( $type, KIRKI_CONTENT_MANAGER_PREFIX ) ) {
			$post_parent = str_replace( KIRKI_CONTENT_MANAGER_PREFIX . '_', '', $collection_data['type'] );
			$post        = ContentManagerHelper::get_post_type( $post_parent, true );

			if ( ! empty( array_filter( $post['fields'], fn( $obj) => $obj['type'] === 'reference' ) ) ) {
				$data['typeValues'][ $element_content_type ] = array_merge(
					$data['typeValues'][ $element_content_type ],
					array(
						array(
							'value' => 'reference',
							'title' => 'Reference',
						),
					)
				);

				$data['availableRefNames'] = array_reduce(
					array_filter( $post['fields'], fn( $obj) => $obj['type'] === 'reference' ),
					function ( $carry, $obj ) {
						$carry[] = array(
							'value'   => $obj['id'],
							'title'   => $obj['title'],
							'post_id' => $obj['ref_collection'],
						);
						return $carry;
					},
					array()
				);

				$data['typeValuesAttr'][ $element_content_type ]['reference'] = array_reduce(
					array_filter( $post['fields'], fn( $obj) => $obj['type'] === 'reference' ),
					function ( $carry, $obj ) use ( $data, $element_content_type ) {
						$carry[ $obj['id'] ] = array_merge(
							$data['typeValuesAttr'][ $element_content_type ]['post'],
							self::convertKirkiCMSFieldsToSelectOptions( $element_content_type, $obj['fields'][0]['fields'] )
						);
						return $carry;
					},
					array()
				);
			}

			$data['typeValues'][ $element_content_type ] = array_map(
				fn( $obj) => $obj['value'] === 'post' ? array(
					'value' => $obj['value'],
					'title' => $post['post_title'],
				) : array(
					'value' => $obj['value'],
					'title' => $obj['title'],
				),
				$data['typeValues'][ $element_content_type ]
			);

			$data['typeValuesAttr'][ $element_content_type ]['post'] = array_merge(
				$data['typeValuesAttr'][ $element_content_type ]['post'],
				self::convertKirkiCMSFieldsToSelectOptions( $element_content_type, $post['fields'] )
			);
		}

		$data = apply_filters( 'kirki_dynamic_content_fields', $data, $collection_data );
		wp_send_json( $data );
	}
}
