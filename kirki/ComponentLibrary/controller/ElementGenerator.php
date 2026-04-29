<?php


namespace KirkiComponentLib;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class ElementGenerator {

	private $element                       = array();
	private $elements                      = array();
	private $attributes                    = array();
	private $setting                       = array();
	private $options                       = array();
	private $generate_child_element        = null;
	private $get_data_and_styles_from_root = null;
	private $style_blocks                  = array();
	private $properties                    = array();
	public $component_lib_forms            = array();
	private $exceptional_elements          = array(
		'kirki-logout',
		'kirki-comment',
	);

	public function __construct( $props ) {
		$this->element                       = $props['element'];
		$this->elements                      = $props['elements'];
		$this->attributes                    = $props['attributes'];
		$this->options                       = $props['options'];
		$this->generate_child_element        = $props['generate_child_element'];
		$this->properties                    = $this->element['properties'];
		$this->setting                       = $this->properties['settings'];
		$this->component_lib_forms           = $props['component_lib_forms'];
		$this->get_data_and_styles_from_root = $props['get_data_and_styles_from_root'];
		$this->style_blocks                  = $props['style_blocks'];
		$this->add_element_config();
	}


	private function add_element_config() {
		$id = $this->element['id'];
		if (
		$this->element['name'] === 'kirki-login' || $this->element['name'] === 'kirki-register' ||
		$this->element['name'] === 'kirki-forgot-password' || $this->element['name'] === 'kirki-change-password' ||
		$this->element['name'] === 'kirki-retrieve-username' || $this->element['name'] === 'kirki-comment'
		) {
			$nonce                            = $this->add_nonce_to_element( $this->element );
			$this->component_lib_forms[ $id ] = array_merge(
				$this->properties['attributes'],
				$this->setting,
				array(
					'name'  => $this->element['name'],
					'nonce' => $nonce,
				)
			);
		}
	}

	public function generate_common_element( $hide = false, $children_html = false ) {
		if ( in_array( $this->element['name'], $this->exceptional_elements, true ) ) {
			return $this->generate_exceptional_element( $this->element['name'], $hide, $children_html );
		}

		$extra_attributes = '';
		if ( $hide ) {
			$extra_attributes .= ' data-element_hide="true"';
		}

		$html         = '';
		$tag          = isset( $this->properties['tag'] ) ? $this->properties['tag'] : 'div';
		$name         = $this->element['name'];
		$can_register = get_option( 'users_can_register' );

		if ( $name === 'kirki-register' && $can_register !== '1' ) {
			return '';
		}

		if ( ! $children_html ) {
			$children_html = $this->generate_child_elements();
		}
		$html = "<$tag $this->attributes data-ele_name='$name' $extra_attributes>$children_html</$tag>";
		return $html;
	}

	private function generate_child_elements() {
		$html        = '';
		$child_count = isset( $this->element['children'] ) ? count( $this->element['children'] ) : 0;
		for ( $i = 0; $i < $child_count; $i++ ) {
			$html .= call_user_func( $this->generate_child_element, $this->element['children'][ $i ], $this->options );
		}
		return $html;
	}

	private function generate_exceptional_element( $name, $hide = false, $children_html = false ) {
		$extra_attributes = '';
		if ( $hide ) {
			$extra_attributes .= ' data-element_hide="true"';
		}

		if ( ! $children_html ) {
			$children_html = $this->generate_child_elements();
		}

		$tag  = isset( $this->properties['tag'] ) ? $this->properties['tag'] : 'div';
		$name = $this->element['name'];

		switch ( $name ) {
			case 'kirki-logout': {
				$user = wp_get_current_user();
				if ( $user->ID === 0 ) {
					return '';
				}

				$href = '';
				$attr = $this->attributes;
				if (
				isset(
					$this->element,
					$this->element['properties'],
					$this->element['properties']['settings'],
					$this->element['properties']['settings']['redirect_url']
				) &&
				strlen( $this->element['properties']['settings']['redirect_url'] ) > 0
				) {
					$href = wp_logout_url( $this->element['properties']['settings']['redirect_url'] );
					$attr = preg_replace( '/href="([^"]+")/i', '', $attr );
					$attr = $attr . 'href=' . $href;
				}
				return "<$tag $attr data-ele_name='$name' $extra_attributes>$children_html</$tag>";
			}
			case 'kirki-comment': {
				$post_id = get_the_ID();
				if ( isset( $this->options['post'] ) && isset( $this->options['post']->ID ) ) {
					$post_id = $this->options['post']->ID;
				}
				if ( isset( $this->options['comment'] ) && isset( $this->options['comment']['comment_post_ID'] ) ) {
					$post_id = $this->options['comment']['comment_post_ID'];
				}

				$comment_parent = 0;
				$comment_id     = 0;
				if ( isset( $this->options['comment'] ) ) {
					$comment = $this->options['comment'];
					if ( isset( $comment['id'] ) ) {
						$comment_parent = $comment['id'];
						// $comment_id = $comment['id'];
					}
				}

				$parent_id = $this->element['parentId'];
				while ( isset( $this->elements[ $parent_id ] ) && $this->elements[ $parent_id ]['name'] !== 'collection' ) {
					if ( $this->elements[ $parent_id ]['name'] === 'body' ) {
						$parent_id = false;
						break;
					}
					$parent_id = $this->elements[ $parent_id ]['parentId'];
				}
				$collection_type = '';
				if ( $parent_id && isset( $this->elements[ $parent_id ]['properties']['dynamicContent'] ) ) {
					$collection_type = $this->elements[ $parent_id ]['properties']['dynamicContent']['type'];
				}

				$kirki_data = '';
				if ( isset( $this->elements[ $this->element['parentId'] ] ) ) {
					$data_n_styles = array(
						'blocks' => array(),
						'styles' => array(),
						'root'   => $this->element['parentId'],
					);
					call_user_func_array( $this->get_data_and_styles_from_root, array( $this->element['parentId'], &$data_n_styles, &$this->elements, &$this->style_blocks ) );
					$encoded_data = json_encode( $data_n_styles );
					$kirki_data  .= "<textarea data-type='kirki_data' style='display: none'>" . esc_textarea( $encoded_data ) . '</textarea>';
				}

				$limit_per_user = isset( $this->properties['settings']['limit_per_user'] ) ? $this->properties['settings']['limit_per_user'] : false;
				if ( $limit_per_user ) {
					$user = wp_get_current_user();
					if ( $user->ID === 0 ) {
						return '';
					}
					$comment_type = $collection_type;
					$type         = explode( '-', $collection_type );
					if ( isset( $type[1] ) ) {
						$comment_type = $type[1];
					}
					global $wpdb;
					$comment_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_parent = %d AND comment_type = %s",
							$post_id,
							$user->ID,
							$comment_parent,
							$comment_type
						)
					);
					if ( $comment_count >= $limit_per_user ) {
						return '';
					}
				}

				if ( ! $children_html ) {
					$children_html = $this->generate_child_elements();
				}

				$hidden_data_html  = "<input type='hidden' name='post_id' value='" . esc_attr( $post_id ) . "' />";
				$hidden_data_html .= "<input type='hidden' name='comment_parent' value='" . esc_attr( $comment_parent ) . "' />";
				$hidden_data_html .= "<input type='hidden' name='comment_id' value='" . esc_attr( $comment_id ) . "' />";
				$hidden_data_html .= "<input type='hidden' name='collection_type' value='" . esc_attr( $collection_type ) . "' />";
				$hidden_data_html .= "<input type='hidden' name='collection_id' value='" . esc_attr( $parent_id ) . "' />";
				$children_html     = $hidden_data_html . $kirki_data . $children_html;
				$html              = "<$tag $this->attributes data-ele_name='$name' $extra_attributes>$children_html</$tag>";
				return $html;
			}
		}
	}

	private function add_nonce_to_element( $element ) {
		if ( empty( $element['name'] ) ) {
			return false;
		}

		$action = KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '_' . $element['name'];

		// Always returns consistent nonce for same user + action for ~12 hours.
		return wp_create_nonce( $action );
	}

}
