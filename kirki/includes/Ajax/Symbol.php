<?php
/**
 * Manage Symbol
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;


/**
 * Symbol API Class
 */
class Symbol {

	/**
	 * Create/save a symbol
	 *
	 * @return void wp_send_json.
	 * 
	 * @deprecated
	 * @see \Kirki\App\Managers\SymbolManager::save()
	 */
	public static function save() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_symbol_data = isset( $_POST['data'] ) ? $_POST['data'] : null;
		if ( ! empty( $post_symbol_data ) ) {
			$wp_post = array(
				'post_type' => KIRKI_SYMBOL_TYPE,
			);

			$post_id = wp_insert_post( $wp_post );
			if ( isset( $post_id ) ) {

				$symbol_data = json_decode( stripslashes( $post_symbol_data ), true );
				$symbol_data['data'][ $symbol_data['root'] ]['properties']['symbolId'] = $post_id;

				add_post_meta( $post_id, 'kirki', $symbol_data );
				add_post_meta( $post_id, KIRKI_META_NAME_FOR_POST_EDITOR_MODE, 'kirki' );

				$data         = array(
					'id'         => $post_id,
					'symbolData' => $symbol_data,
					'type'       => isset( $symbol_data['category'] ) ? $symbol_data['category'] : 'other',
				);
				$data['rootSizeVariant'] = self::get_symbol_root_size_variant( $symbol_data );
				$data['html'] = self::get_symbol_html_preview( $data );
				wp_send_json( $data );
			}
		};

		die();
	}

	/**
	 * @deprecated
	 * @see \Kirki\App\Managers\SymbolManager::save()
	 */
	public static function save_to_db( $data ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_symbol_data = $data ? $data : null;

		if ( ! empty( $post_symbol_data ) ) {
			unset( $post_symbol_data['id'] );
			unset( $post_symbol_data['elementId'] );

			$post_symbol_data = $post_symbol_data['symbolData'];

			$wp_post = array(
				'post_type' => KIRKI_SYMBOL_TYPE,
			);

			$post_id = wp_insert_post( $wp_post );

			if ( isset( $post_id ) ) {

				$symbol_data = $post_symbol_data;
				$symbol_data['data'][ $symbol_data['root'] ]['properties']['symbolId'] = $post_id;

				add_post_meta( $post_id, 'kirki', $symbol_data );
				add_post_meta( $post_id, KIRKI_META_NAME_FOR_POST_EDITOR_MODE, 'kirki' );

				$data         = array(
					'id'         => $post_id,
					'symbolData' => $symbol_data,
					'type'       => isset( $symbol_data['category'] ) ? $symbol_data['category'] : 'other',
				);
				$data['rootSizeVariant'] = self::get_symbol_root_size_variant( $symbol_data );
				$data['html'] = self::get_symbol_html_preview( $data );

				return $data;
			}
		};

		return null;

	}

	/**
	 * Fetch symbol list
	 * if $internal is true then it will return array
	 * otherwise it will return json for api call
	 *
	 * @param boolean $internal if function call from internally.
	 * @param boolean $html if need html string.
	 * @return array|void wp_send_json.
	 */
	public static function fetch_list( $internal = false, $html = false ) {
		$posts = get_posts(
			array(
				'post_type'   => KIRKI_SYMBOL_TYPE,
				'post_status' => 'draft',
				'numberposts' => -1,
			)
		);

		$symbols = array();

		if ( ! empty( $posts ) ) {
			$symbols = array_map(
				function( $post ) use ( $html, $internal ) {
					 $single_symbol = self::get_single_symbol( $post->ID, true, $html );
					if ( ! $internal ) {
						try {
							unset( $single_symbol['symbolData']['data'] );
							unset( $single_symbol['symbolData']['styleBlocks'] );
						} catch ( \Throwable $th ) {
							// throw $th;
						}
					}
					 return $single_symbol;
				},
				$posts
			);
		}

		if ( $internal ) {
			return $symbols;
		}
		wp_send_json( $symbols );
	}
	/**
	 * Fetch symbol
	 *
	 * @return void wp_send_json.
	 */
	public static function fetch_symbol() {
		$symbol_id              = HelperFunctions::sanitize_text( isset( $_POST['id'] ) ? $_POST['id'] : null );
		$symbol_element_prop    = isset( $_POST['symbolElementProp'] ) ? $_POST['symbolElementProp'] : null;
		$component_field_values = isset( $_POST['componentFieldValues'] ) ? $_POST['componentFieldValues'] : null;
		$collection_item        = isset( $_POST['collectionItem'] ) ? $_POST['collectionItem'] : null;
		$variable_css           = isset( $_POST['variableCSS'] ) ? $_POST['variableCSS'] : true;
		$options                = $collection_item && 'false' !== $collection_item
			? json_decode( stripslashes( $collection_item ), true )
			: array();
		$options                = is_array( $options ) ? $options : array();
		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) && $key !== 'user' ) {
				$options[ $key ] = json_decode( json_encode( $value ) );
			}
		}
		self::get_single_symbol( $symbol_id, false, true, $symbol_element_prop, $options, $variable_css, $component_field_values );
	}


	/**
	 * get single symbol
	 * if $internal is true then it will return array
	 * otherwise it will return json for api call
	 *
	 * @param int     $symbol_id symbol id.
	 * @param boolean $internal if the function call from internally.
	 * @param boolean $html if need html preview string.
	 * @param array   $symbol_element_prop Legacy element-level instance overrides.
	 * @param array   $options Rendering context such as the current collection item.
	 * @param boolean $variable_css Whether variable CSS is required.
	 * @param array   $component_field_values Public values keyed by component-field ID.
	 * @return array|void
	 */
	public static function get_single_symbol(
		$symbol_id = null,
		$internal = false,
		$html = false,
		$symbol_element_prop = false,
		$options = array(),
		$variable_css = true,
		$component_field_values = false
	) {
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$symbol_id = $symbol_id ? $symbol_id : HelperFunctions::sanitize_text( isset( $_GET['symbol_id'] ) ? $_GET['symbol_id'] : null );
		$post      = get_post( $symbol_id );
		$symbol    = null;
		if ( $post && $post->post_type == KIRKI_SYMBOL_TYPE ) {
			$symbol               = array();
			$symbol_data          = get_post_meta( $post->ID, 'kirki', true );
			$symbol['id']         = $post->ID;
			$symbol['symbolData'] = $symbol_data;
			$symbol['type']       = isset( $symbol_data['category'] ) ? $symbol_data['category'] : 'other';
			$symbol['setAs']      = isset( $symbol_data['setAs'] ) ? $symbol_data['setAs'] : '';
			$symbol['rootSizeVariant'] = self::get_symbol_root_size_variant( $symbol_data );

			if ( ! $internal ) {
				$symbol_element_prop = json_decode( stripslashes( $symbol_element_prop ), true );
				$component_field_values = json_decode( stripslashes( $component_field_values ), true );
			}
			if ( $symbol_element_prop !== false && is_array( $symbol_element_prop ) ) {
				// Append instance-only root styles after the master's styles so explicit overrides win.
				$root_style_override = isset( $symbol_element_prop['__rootStyle']['variant'] )
					? array_filter(
						array_map(
							array( self::class, 'get_symbol_root_size_css' ),
							$symbol_element_prop['__rootStyle']['variant']
						)
					)
					: array();
				$root_id             = isset( $symbol['symbolData']['root'] ) ? $symbol['symbolData']['root'] : null;

				if ( $root_id && ! empty( $root_style_override ) && isset( $symbol['symbolData']['data'][ $root_id ] ) ) {
					$override_style_id = uniqid( 'kirki-instance-style-' );
					$symbol['symbolData']['styleBlocks'][ $override_style_id ] = array(
						'id'      => $override_style_id,
						'type'    => 'class',
						// Two classes make the instance rule more specific than master rules
						// injected later by another instance of the same symbol.
						'name'    => array( $override_style_id, 'kirki-symbol-root-size-override' ),
						'variant' => $root_style_override,
					);
					$root_style_ids = isset( $symbol['symbolData']['data'][ $root_id ]['styleIds'] )
						? $symbol['symbolData']['data'][ $root_id ]['styleIds']
						: array();
					$symbol['symbolData']['data'][ $root_id ]['styleIds'] = array_merge(
						$root_style_ids,
						array( $override_style_id )
					);
				}

				foreach ( $symbol_element_prop as $key => $value ) {
					foreach ( $symbol['symbolData']['data'] as $key2 => $value2 ) {
						$has_symbol_el_prop_id = isset( $value2['properties']['symbolElPropId'] )
							&& '' !== $value2['properties']['symbolElPropId'];
						$matches_symbol_el_prop = $has_symbol_el_prop_id && $value2['properties']['symbolElPropId'] == $key;
						$matches_legacy_element = ! $has_symbol_el_prop_id && (
							$key2 == $key || ( isset( $value2['id'] ) && $value2['id'] == $key )
						);
						if ( $matches_symbol_el_prop || $matches_legacy_element ) {
							if ( isset( $value['contents'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['contents'] = $value['contents'];
							}
							if ( isset( $value['contentElement'] ) ) {
								foreach ( $value['contentElement'] as $new_item ) {
									if ( isset( $new_item['id'] ) ) {
											$symbol['symbolData']['data'][ $new_item['id'] ] = $new_item;
									}
								}
							}
							if ( isset( $value['attributes'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['attributes'] = $value['attributes'];
							}
							if ( isset( $value['wp_attachment_id'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['wp_attachment_id'] = $value['wp_attachment_id'];
							}
							if ( isset( $value['lottie'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['lottie'] = $value['lottie'];
							}
							if ( isset( $value['svgOuterHtml'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['svgOuterHtml'] = $value['svgOuterHtml'];
							}
							if ( isset( $value['tag'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['tag'] = $value['tag'];
							}
							if ( isset( $value['type'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['type'] = $value['type'];
							}
							if ( isset( $value['isActive'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['isActive'] = $value['isActive'];
							}
							if ( isset( $value['preload'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['preload'] = $value['preload'];
							}
							if ( isset( $value['dynamicContent'] ) ) {
								$symbol['symbolData']['data'][ $key2 ]['properties']['dynamicContent'] = $value['dynamicContent'];
							}
							if ( isset( $value['symbolElProps'] ) && is_array( $value['symbolElProps'] ) ) {
								$current_nested_props = isset( $symbol['symbolData']['data'][ $key2 ]['properties']['symbolElProps'] )
									? $symbol['symbolData']['data'][ $key2 ]['properties']['symbolElProps']
									: array();
								$symbol['symbolData']['data'][ $key2 ]['properties']['symbolElProps'] = array_replace_recursive(
									$current_nested_props,
									$value['symbolElProps']
								);
							}
							if (
								isset( $symbol['symbolData']['data'][ $key2 ]['properties']['dynamicContent'] ) &&
								$symbol['symbolData']['data'][ $key2 ]['properties']['dynamicContent']['type'] === 'manual'
							) {
								unset( $symbol['symbolData']['data'][ $key2 ]['properties']['dynamicContent'] );
							}
						}
					}
				}
			}

			if ( is_array( $component_field_values ) ) {
				$symbol['symbolData'] = self::apply_component_field_values(
					$symbol['symbolData'],
					$component_field_values
				);
			}

			if ( $html === true ) {
				$symbol['html'] = self::get_symbol_html_preview( $symbol, $options, $variable_css );
			}
		}

		if ( $internal ) {
			return $symbol;
		}
		wp_send_json( $symbol );
	}

	/**
	 * Resolve public component-field values into the symbol's internal elements.
	 *
	 * @param array $symbol_data Symbol definition.
	 * @param array $field_values Values keyed by public component-field ID.
	 * @return array
	 */
	public static function apply_component_field_values( $symbol_data, $field_values ) {
		if ( empty( $symbol_data['data'] ) || ! is_array( $field_values ) ) {
			return $symbol_data;
		}

		$effective_values = array();
		foreach ( isset( $symbol_data['fields'] ) ? $symbol_data['fields'] : array() as $field ) {
			if ( ! isset( $field['id'] ) || ! array_key_exists( $field['id'], $field_values ) ) {
				continue;
			}

			$effective_values[ $field['id'] ] = $field_values[ $field['id'] ];
		}

		if ( isset( $field_values['__nested'] ) && is_array( $field_values['__nested'] ) ) {
			self::apply_nested_component_field_values( $symbol_data['data'], $field_values['__nested'] );
		}

		foreach ( $symbol_data['data'] as &$element ) {
			$bindings = isset( $element['properties']['componentFieldBindings'] )
				? $element['properties']['componentFieldBindings']
				: array();

			foreach ( $bindings as $slot => $stored_field_ids ) {
				$field_ids = is_array( $stored_field_ids ) ? $stored_field_ids : array( $stored_field_ids );
				foreach ( $field_ids as $field_id ) {
					if ( null === $field_id || ! array_key_exists( $field_id, $effective_values ) ) {
						continue;
					}

					if ( 0 === strpos( $slot, 'instanceField:' ) ) {
						self::assign_nested_component_field_value(
							$element,
							$slot,
							$effective_values[ $field_id ]
						);
						continue;
					}

					self::apply_component_field_value_to_element(
						$element,
						$slot,
						$effective_values[ $field_id ]
					);
				}
			}
		}
		unset( $element );

		return $symbol_data;
	}

	/**
	 * Attach values intended for deeper nested instances to their immediate owner.
	 *
	 * @param array $data Symbol elements.
	 * @param array $nested_values Nested values keyed by symbolElPropId.
	 * @return void
	 */
	private static function apply_nested_component_field_values( &$data, $nested_values ) {
		foreach ( $data as &$element ) {
			$symbol_el_prop_id = isset( $element['properties']['symbolElPropId'] )
				? $element['properties']['symbolElPropId']
				: null;

			if ( ! $symbol_el_prop_id || ! isset( $nested_values[ $symbol_el_prop_id ] ) ) {
				continue;
			}

			$current_values = isset( $element['properties']['componentFieldValues'] )
				? $element['properties']['componentFieldValues']
				: array();
			$element['properties']['componentFieldValues'] = self::merge_component_field_values(
				$current_values,
				$nested_values[ $symbol_el_prop_id ]
			);
		}
		unset( $element );
	}

	/**
	 * Merge nested routing maps while replacing each field entry as one value.
	 *
	 * @param array $current_values Existing values on the nested instance.
	 * @param array $next_values Incoming field values.
	 * @return array
	 */
	private static function merge_component_field_values( $current_values, $next_values ) {
		$result = is_array( $current_values ) ? $current_values : array();
		if ( ! is_array( $next_values ) ) {
			return $result;
		}

		foreach ( $next_values as $key => $value ) {
			if ( '__nested' === $key && is_array( $value ) ) {
				if ( ! isset( $result['__nested'] ) || ! is_array( $result['__nested'] ) ) {
					$result['__nested'] = array();
				}

				foreach ( $value as $symbol_el_prop_id => $nested_field_values ) {
					$current_nested_values = isset( $result['__nested'][ $symbol_el_prop_id ] )
						? $result['__nested'][ $symbol_el_prop_id ]
						: array();
					$result['__nested'][ $symbol_el_prop_id ] = self::merge_component_field_values(
						$current_nested_values,
						$nested_field_values
					);
				}
				continue;
			}

			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Convert an instanceField binding path into nested component field values.
	 *
	 * @param array  $element Nested symbol instance element.
	 * @param string $slot Binding slot.
	 * @param mixed  $value Field value.
	 * @return void
	 */
	private static function assign_nested_component_field_value( &$element, $slot, $value ) {
		$path = array_values(
			array_filter(
				explode( ':', substr( $slot, strlen( 'instanceField:' ) ) ),
				'strlen'
			)
		);
		$field_id = array_pop( $path );

		if ( ! $field_id ) {
			return;
		}

		if ( ! isset( $element['properties']['componentFieldValues'] ) ) {
			$element['properties']['componentFieldValues'] = array();
		}

		$target_values =& $element['properties']['componentFieldValues'];
		foreach ( $path as $symbol_el_prop_id ) {
			if ( ! isset( $target_values['__nested'][ $symbol_el_prop_id ] ) ) {
				$target_values['__nested'][ $symbol_el_prop_id ] = array();
			}
			$target_values =& $target_values['__nested'][ $symbol_el_prop_id ];
		}
		$target_values[ $field_id ] = $value;
	}

	/**
	 * Unwrap a compact component-field entry.
	 *
	 * @param mixed $entry Stored entry.
	 * @return array
	 */
	private static function get_component_field_entry( $entry ) {
		$is_wrapped = is_array( $entry )
			&& isset( $entry['__componentFieldValue'] )
			&& true === $entry['__componentFieldValue'];

		return array(
			'value'           => $is_wrapped && array_key_exists( 'value', $entry ) ? $entry['value'] : $entry,
			'dynamicContent'  => $is_wrapped && isset( $entry['dynamicContent'] ) ? $entry['dynamicContent'] : null,
		);
	}

	/**
	 * Apply one typed field value to an element property slot.
	 *
	 * @param array  $element Element data.
	 * @param string $slot Component field slot.
	 * @param mixed  $entry Compact field value entry.
	 * @return void
	 */
	private static function apply_component_field_value_to_element( &$element, $slot, $entry ) {
		$field_entry = self::get_component_field_entry( $entry );
		$value       = $field_entry['value'];

		if ( ! isset( $element['properties'] ) ) {
			$element['properties'] = array();
		}
		if ( ! isset( $element['properties']['attributes'] ) ) {
			$element['properties']['attributes'] = array();
		}

		if ( null !== $field_entry['dynamicContent'] ) {
			$dynamic_property = 'imageAlt' === $slot ? 'dynamicAlt' : 'dynamicContent';
			$element['properties'][ $dynamic_property ] = $field_entry['dynamicContent'];
			return;
		}

		// An explicit static instance value must win over an older dynamic override.
		$dynamic_property = 'imageAlt' === $slot ? 'dynamicAlt' : 'dynamicContent';
		unset( $element['properties'][ $dynamic_property ] );

		switch ( $slot ) {
			case 'content':
				$element['properties']['contents'] = array( (string) $value );
				break;

			case 'imageSource':
			case 'videoSource':
				$media = is_array( $value ) ? $value : array( 'url' => $value );
				$src   = isset( $media['url'] ) ? $media['url'] : ( isset( $media['sources']['original'] ) ? $media['sources']['original'] : '' );
				$element['properties']['attributes']['src'] = $src;
				if ( isset( $media['name'] ) ) {
					$element['properties']['attributes']['name'] = $media['name'];
				}
				$element['properties']['wp_attachment_id'] = isset( $media['id'] ) ? $media['id'] : '';
				break;

			case 'imageAlt':
				$element['properties']['attributes']['alt'] = (string) $value;
				break;

			case 'linkUrl':
				$link = is_array( $value ) ? $value : array();
				$element['properties']['type'] = isset( $link['type'] ) ? $link['type'] : 'href';
				$element['properties']['preload'] = isset( $link['preload'] ) ? $link['preload'] : 'default';
				$element['properties']['attributes'] = isset( $link['attributes'] )
					? array_replace( $element['properties']['attributes'], $link['attributes'] )
					: array_replace(
						$element['properties']['attributes'],
						array(
							'href'   => (string) $value,
							'target' => '',
							'rel'    => array( '' ),
						)
					);
				break;

			case 'icon':
				$icon = is_array( $value ) ? $value : array();
				$element['properties']['tag'] = 'svg';
				$element['properties']['name'] = isset( $icon['name'] ) ? $icon['name'] : '';
				$element['properties']['svgOuterHtml'] = isset( $icon['svgOuterHtml'] ) ? $icon['svgOuterHtml'] : '';
				$element['properties']['library'] = isset( $icon['library'] ) ? $icon['library'] : '';
				break;

			case 'svg':
				$element['properties']['svgOuterHtml'] = (string) $value;
				break;

			case 'color':
			case 'fill':
				self::apply_component_fill_value( $element, $slot, $value );
				break;
		}
	}

	/**
	 * Apply color/fill values to an element's inline declarations.
	 *
	 * @param array  $element Element data.
	 * @param string $slot Color or fill slot.
	 * @param mixed  $value Fill value.
	 * @return void
	 */
	private static function apply_component_fill_value( &$element, $slot, $value ) {
		$fill = is_array( $value ) ? $value : array( 'type' => 'solid', 'value' => $value );
		$type = isset( $fill['type'] ) ? $fill['type'] : null;
		if ( ! $type ) {
			return;
		}

		$element['properties']['value'] = $value;
		$background_image = self::get_component_background_image( $fill );
		if ( 'color' === $slot ) {
			$declarations = 'solid' === $type
				? array(
					'color'                   => isset( $fill['value'] ) ? $fill['value'] : '',
					'background-color'        => 'transparent',
					'background-image'        => 'none',
					'background-clip'         => 'unset',
					'-webkit-background-clip' => 'unset',
					'-webkit-text-fill-color' => 'unset',
				)
				: array(
					'color'                   => 'transparent',
					'background-color'        => 'transparent',
					'background-image'        => $background_image,
					'background-clip'         => 'text',
					'-webkit-background-clip' => 'text',
					'-webkit-text-fill-color' => 'transparent',
				);
		} else {
			$declarations = 'solid' === $type
				? array(
					'background-color' => isset( $fill['value'] ) ? $fill['value'] : '',
					'background-image' => 'none',
				)
				: array(
					'background-color' => 'transparent',
					'background-image' => $background_image,
				);
		}

		$current_style = isset( $element['properties']['attributes']['style'] )
			? $element['properties']['attributes']['style']
			: '';
		$element['properties']['attributes']['style'] = self::update_inline_style( $current_style, $declarations );
	}

	/**
	 * Convert a fill object to a CSS background image.
	 *
	 * @param array $fill Fill configuration.
	 * @return string
	 */
	private static function get_component_background_image( $fill ) {
		$type = isset( $fill['type'] ) ? $fill['type'] : '';
		if ( 'image' === $type ) {
			return isset( $fill['value'] ) && $fill['value'] ? $fill['value'] : 'url("#")';
		}
		if ( 'none' === $type ) {
			return 'none';
		}

		$stops = array();
		foreach ( isset( $fill['stops'] ) ? $fill['stops'] : array() as $stop ) {
			$stops[] = ( isset( $stop['color'] ) ? $stop['color'] : '' ) . ' ' . self::add_component_css_unit( isset( $stop['position'] ) ? $stop['position'] : array() );
		}
		$color_stops = implode( ', ', $stops );
		$repeating   = ! empty( $fill['repeating'] ) ? 'repeating-' : '';

		if ( 'linear' === $type ) {
			$angle = isset( $fill['angle'] ) ? $fill['angle'] : array( 'value' => 90, 'unit' => 'deg' );
			return $repeating . 'linear-gradient(' . self::add_component_css_unit( $angle, 90, 'deg' ) . ', ' . $color_stops . ')';
		}
		if ( 'radial' === $type ) {
			$position = isset( $fill['position'] ) ? $fill['position'] : array();
			$size     = isset( $fill['size'] ) ? $fill['size'] : array();
			return $repeating . 'radial-gradient(ellipse '
				. self::add_component_css_unit( isset( $size['x'] ) ? $size['x'] : array() ) . ' '
				. self::add_component_css_unit( isset( $size['y'] ) ? $size['y'] : array() ) . ' at '
				. self::add_component_css_unit( isset( $position['x'] ) ? $position['x'] : array() ) . ' '
				. self::add_component_css_unit( isset( $position['y'] ) ? $position['y'] : array() ) . ', '
				. $color_stops . ')';
		}
		if ( 'conic' === $type ) {
			$position = isset( $fill['position'] ) ? $fill['position'] : array();
			$angle    = isset( $fill['conicAngle'] ) ? $fill['conicAngle'] : 0;
			return $repeating . 'conic-gradient(from ' . $angle . ' at '
				. self::add_component_css_unit( isset( $position['x'] ) ? $position['x'] : array() ) . ' '
				. self::add_component_css_unit( isset( $position['y'] ) ? $position['y'] : array() ) . ', '
				. $color_stops . ')';
		}

		return '';
	}

	/**
	 * Add a unit to a component fill value.
	 *
	 * @param mixed  $value Value or value/unit pair.
	 * @param mixed  $default_value Default numeric value.
	 * @param string $default_unit Default unit.
	 * @return string
	 */
	private static function add_component_css_unit( $value, $default_value = 0, $default_unit = '%' ) {
		if ( is_array( $value ) ) {
			$number = isset( $value['value'] ) ? $value['value'] : $default_value;
			$unit   = isset( $value['unit'] ) ? $value['unit'] : $default_unit;
			return $number . $unit;
		}

		return (string) $value;
	}

	/**
	 * Merge CSS declarations into an inline style string.
	 *
	 * @param string $style Existing style.
	 * @param array  $declarations Declarations to set.
	 * @return string
	 */
	private static function update_inline_style( $style, $declarations ) {
		$style_map = array();
		foreach ( explode( ';', (string) $style ) as $declaration ) {
			$parts = explode( ':', $declaration, 2 );
			if ( 2 === count( $parts ) && '' !== trim( $parts[0] ) ) {
				$style_map[ trim( $parts[0] ) ] = trim( $parts[1] );
			}
		}

		foreach ( $declarations as $property => $property_value ) {
			if ( '' !== $property_value && null !== $property_value ) {
				$style_map[ $property ] = $property_value;
			} else {
				unset( $style_map[ $property ] );
			}
		}

		$next_style = array();
		foreach ( $style_map as $property => $property_value ) {
			$next_style[] = $property . ': ' . $property_value;
		}
		return implode( '; ', $next_style );
	}

	/**
	 * Get the CSS properties that control a symbol instance root's size.
	 *
	 * @return array
	 */
	private static function get_symbol_root_size_properties() {
		return array( 'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height' );
	}

	/**
	 * Keep only root sizing declarations for a symbol instance root.
	 *
	 * @param string $css CSS declarations.
	 * @return string
	 */
	public static function get_symbol_root_size_css( $css ) {
		$property_pattern = implode( '|', self::get_symbol_root_size_properties() );

		preg_match_all(
			'/(?:^|;)\s*(' . $property_pattern . ')\s*:\s*([^;]*)(?=;|$)/i',
			$css,
			$matches,
			PREG_SET_ORDER
		);

		$size_css = '';
		foreach ( $matches as $match ) {
			$value = trim( $match[2] );
			if ( '' !== $value ) {
				$size_css .= strtolower( $match[1] ) . ':' . $value . ';';
			}
		}

		return $size_css;
	}

	/**
	 * Get one root size declaration from CSS.
	 *
	 * @param string $css CSS declarations.
	 * @param string $property Root sizing property.
	 * @return string
	 */
	public static function get_symbol_root_size_property_css( $css, $property ) {
		if ( ! in_array( $property, self::get_symbol_root_size_properties(), true ) ) {
			return '';
		}

		preg_match(
			'/(?:^|;)\s*' . $property . '\s*:\s*([^;]*)(?=;|$)/i',
			$css,
			$match
		);
		$value = isset( $match[1] ) ? trim( $match[1] ) : '';

		return '' !== $value ? $property . ':' . $value . ';' : '';
	}

	/**
	 * Remove root sizing declarations from a CSS declaration string.
	 *
	 * @param string $css CSS declarations.
	 * @return string
	 */
	public static function remove_symbol_root_size_css( $css ) {
		$property_pattern = implode( '|', self::get_symbol_root_size_properties() );
		$css              = ';' . $css;
		$css = preg_replace(
			'/;\s*(?:' . $property_pattern . ')\s*:\s*[^;]*(?=;|$)/i',
			'',
			$css
		);

		return ltrim( $css, ';' );
	}

	/**
	 * Replace inherited root sizes while retaining unrelated instance CSS.
	 *
	 * @param array $current_variant Current instance variants.
	 * @param array $root_size_variant Current master size variants.
	 * @param array $overrides Explicit instance overrides by variant.
	 * @return array
	 */
	public static function merge_symbol_root_size_variant( $current_variant, $root_size_variant, $overrides = array() ) {
		$current_variant   = is_array( $current_variant ) ? $current_variant : array();
		$root_size_variant = is_array( $root_size_variant ) ? $root_size_variant : array();
		$overrides         = is_array( $overrides ) ? $overrides : array();
		$variant_keys      = array_unique( array_merge( array_keys( $current_variant ), array_keys( $root_size_variant ) ) );
		$next_variant      = array();

		foreach ( $variant_keys as $variant_key ) {
			$current_css       = isset( $current_variant[ $variant_key ] ) ? $current_variant[ $variant_key ] : '';
			$master_css        = isset( $root_size_variant[ $variant_key ] ) ? $root_size_variant[ $variant_key ] : '';
			$variant_overrides = isset( $overrides[ $variant_key ] ) && is_array( $overrides[ $variant_key ] )
				? $overrides[ $variant_key ]
				: array();
			$css               = self::remove_symbol_root_size_css( $current_css );

			foreach ( self::get_symbol_root_size_properties() as $property ) {
				$source_css = in_array( $property, $variant_overrides, true ) ? $current_css : $master_css;
				$css       .= self::get_symbol_root_size_property_css( $source_css, $property );
			}

			if ( '' !== $css ) {
				$next_variant[ $variant_key ] = $css;
			}
		}

		return $next_variant;
	}

	/**
	 * Get the responsive root sizing declarations from a symbol master root.
	 *
	 * @param array $symbol_data Symbol data payload.
	 * @return array
	 */
	public static function get_symbol_root_size_variant( $symbol_data ) {
		$root_id = isset( $symbol_data['root'] ) ? $symbol_data['root'] : null;
		$root    = $root_id && isset( $symbol_data['data'][ $root_id ] )
			? $symbol_data['data'][ $root_id ]
			: null;

		if ( ! $root ) {
			return array();
		}

		$properties = isset( $root['properties'] ) ? $root['properties'] : array();
		$style_ids  = array_merge(
			isset( $properties['classesIds'] ) ? $properties['classesIds'] : array(),
			isset( $root['styleIds'] ) ? $root['styleIds'] : array()
		);
		$variants   = array();

		foreach ( $style_ids as $style_id ) {
			if ( empty( $symbol_data['styleBlocks'][ $style_id ]['variant'] ) ) {
				continue;
			}

			foreach ( $symbol_data['styleBlocks'][ $style_id ]['variant'] as $variant_key => $css ) {
				$size_css = self::get_symbol_root_size_css( $css );
				if ( '' === $size_css ) {
					continue;
				}

				$variants[ $variant_key ] = isset( $variants[ $variant_key ] )
					? $variants[ $variant_key ] . $size_css
					: $size_css;
			}
		}

		return $variants;
	}

	/**
	 * @deprecated
	 * @see \Kirki\App\Managers\SymbolManager::get_preview_html()
	 */
	private static function get_symbol_html_preview( $symbol, $options = array(), $variable_css = true ) {
		if ( ! $symbol['symbolData'] ) {
			return '';
		}
		$symbol_data = $symbol['symbolData'];

		$params = array(
			'blocks'                 => $symbol_data['data'],
			'style_blocks'           => $symbol_data['styleBlocks'],
			'root'                   => $symbol_data['root'],
			'post_id'                => $symbol['id'],
			'options'                => array_merge(
				$options,
				array( 'include_component_field_metadata' => true )
			),
			'get_style'              => true,
			'get_variable'           => HelperFunctions::isTruthy( $variable_css ),
			'should_take_app_script' => false,
			'prefix'                 => 'kirki-s' . $symbol['id'],
		);

		$s = HelperFunctions::get_html_using_preview_script( $params );
		return $s;
	}

	/**
	 * Update Symbol data
	 *
	 * @return void wp_send_json.
	 */
	public static function update() {
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$symbol_id = HelperFunctions::sanitize_text( isset( $_POST['symbol_id'] ) ? $_POST['symbol_id'] : null );
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_symbol_data = isset( $_POST['data'] ) ? $_POST['data'] : null;
		if ( $symbol_id && ! empty( $post_symbol_data ) ) {
			$data        = json_decode( stripslashes( $post_symbol_data ), true );
			$symbol_data = get_post_meta( $symbol_id, 'kirki', true );

			if ( $symbol_data ) {
				if ( isset( $data['data'] ) ) {
					$symbol_data['data'] = $data['data'];
				}
				if ( isset( $data['styleBlocks'] ) ) {
					$symbol_data['styleBlocks'] = $data['styleBlocks'];
				}
				if ( isset( $data['name'] ) ) {
					$symbol_data['name'] = $data['name'];
				}
				if ( isset( $data['category'] ) ) {
					$symbol_data['category'] = $data['category'];
				}
				if ( isset( $data['root'] ) ) {
					$symbol_data['root'] = $data['root'];
				}
				if ( array_key_exists( 'fields', $data ) ) {
					$symbol_data['fields'] = $data['fields'];
				}
				// if ( isset( $data['conditions'] ) ) {
				// $symbol_data['conditions'] = $data['conditions'];
				// }

				$set_as_toggle_success = true;
				if ( isset( $data['setAs'] ) ) {
					$symbol_data['setAs'] = $data['setAs'];

					if ( $data['setAs'] != '' ) {
						$all_symbol = self::fetch_list( true );
						foreach ( $all_symbol as $key => $value ) {
							if ( $value['id'] != $symbol_id && isset( $value['symbolData'], $value['symbolData']['setAs'] ) && $value['symbolData']['setAs'] == $data['setAs'] ) {
								$all_symbol[ $key ]['symbolData']['setAs'] = '';
								$set_as_toggle_success                     = update_post_meta( $value['id'], 'kirki', $all_symbol[ $key ]['symbolData'] );
							}
						}
					}
				}

				if ( $set_as_toggle_success !== true ) {
					wp_send_json( false );
				}

				$updated = update_post_meta( $symbol_id, 'kirki', $symbol_data );

				$updated === true && $set_as_toggle_success === true ? wp_send_json( self::get_single_symbol( $symbol_id, true, true ) ) : wp_send_json( false );
			} else {
				wp_send_json( false );
			}
		}
		die();
	}

	/**
	 * Delete a symbol
	 *
	 * @return void
	 */
	public static function delete() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$symbol_id = HelperFunctions::sanitize_text( isset( $_POST['symbol_id'] ) ? $_POST['symbol_id'] : null );
		if ( $symbol_id ) {
			$post = wp_delete_post( $symbol_id );
			isset( $post ) ? wp_send_json( true ) : wp_send_json( false );
		}
		die();
	}

	public static function get_pre_built_html_using_url() {
		 $raw_url = isset( $_GET['elementUrl'] ) ? wp_unslash( $_GET['elementUrl'] ) : '';

    if ( empty( $raw_url ) ) {
        wp_send_json_error( 'Missing elementUrl', 400 );
    }
		$allowed_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $requested    = wp_parse_url( $raw_url );

    if (
			! $requested ||
			empty( $requested['host'] ) ||
			strtolower( $requested['host'] ) !== strtolower( $allowed_host )
    ) {
        wp_send_json_error( 'URL not permitted', 403 );
    }

		$resolved_ip = gethostbyname( $requested['host'] );

		if ( self::is_private_or_loopback_ip( $resolved_ip ) ) {
      wp_send_json_error( 'URL not permitted', 403 );
    }

		$safe_url = self::rebuild_url( $requested );

		$response = HelperFunctions::http_get( $safe_url, array(
			'timeout'   => 30,
			'sslverify' => false,   
			'redirection' => 0,  
    ));

		if ( is_wp_error( $response ) ) {
      wp_send_json_error( 'Request failed', 502 );
    }

		$body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );


		$params = array(
			'blocks'                 => $data['blocks'],
			'style_blocks'           => $data['styles'],
			'root'                   => $data['root'],
			'post_id'                => false,
			'options'                => array(),
			'get_style'              => true,
			'get_variable'           => false,
			'should_take_app_script' => false,
		);

		$html = HelperFunctions::get_html_using_preview_script( $params );

		wp_send_json_success( $html );
	}

	/**
 	* Returns true for any IP that should never be reached from a server-side fetch.
 	* Covers loopback, RFC 1918, link-local (APIPA / cloud metadata), and IPv6 equivalents.
 	*/
	private static function is_private_or_loopback_ip( string $ip ): bool {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}


	/**
	 * Reconstructs a URL from wp_parse_url parts so the raw user string
	 * is never passed directly to the HTTP client.
	 */
	private static function rebuild_url( array $parts ): string {
		$scheme = isset( $parts['scheme'] ) && strtolower( $parts['scheme'] ) === 'https' ? 'https' : 'https';
		$host   = $parts['host'];
		$path   = isset( $parts['path'] ) ? $parts['path'] : '/';
		$query  = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return "{$scheme}://{$host}{$path}{$query}";
	}
}
