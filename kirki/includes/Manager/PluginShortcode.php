<?php

/**
 * Plugin Shortcode handler
 *
 * @package kirki
 */

/**
 * So far supported shortcodes:
 * [kirki data="current_year"] - Returns the current year.
 * [kirki data="current_date"] - Returns the current date in "F j, Y" format.
 * [kirki data="reading_time"] - Returns the estimated reading time for the current post.
 * [kirki type="post_meta" data="meta_key"] - Returns the value of the specified post meta key for the current post.
 *
 * [kirki_cm slug="your-content-manager-slug" data="count"] - Returns the count of child posts under the specified content manager slug.
 */

namespace Kirki\Manager;

use Kirki\API\ContentManager\ContentManagerHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Do some task with shortcode
 */

class PluginShortcode {

	/**
	 * Initilize the class
	 *
	 * @return void
	 */
	public function __construct() {
		// Kirki shortcode
		add_shortcode( 'kirki', array( $this, 'kirki_shortcode_handler' ) );

		// Content manager shortcode
		add_shortcode('kirki_cm', array( $this, 'kirki_cm_shortcode_handler' ) );
	}

	private function clean_shortcode_value( $val ) {
		if ( ! is_string( $val ) ) {
			return $val;
		}

		// Decode entities like &#8220; or &#8221;
		$val = html_entity_decode( $val, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$val = trim( $val );

		// Strip wrapping quotes repeatedly (any combination)
		while (
		preg_match(
			'/^([\'"“”‘’]+)(.*?)([\'"“”‘’]+)$/u',
			$val,
			$matches
		)
		) {
			// Only strip if first and last char are quote-like
			$first = mb_substr( $val, 0, 1 );
			$last  = mb_substr( $val, -1 );
			if ( preg_match( '/[\'"“”‘’]/u', $first ) && preg_match( '/[\'"“”‘’]/u', $last ) ) {
				$val = trim( $matches[2] );
			} else {
				break;
			}
		}

		return $val;
	}

	private static function post_estimated_reading_time( $post ) {
		// load the content
		$thecontent = $post->post_content;
		// count the number of words
		$words = str_word_count( wp_strip_all_tags( $thecontent ) );
		// rounding off and deviding per 200 words per minute
		$m = floor( $words / 200 );
		// rounding off to get the seconds
		$s = floor( $words % 200 / ( 200 / 60 ) );
		// calculate the amount of time needed to read
		if ( $m == 0 ) {
			return $s . 's';
		} else {
			return $m . 'm';
		}
	}

	/**
	 * Render the shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function kirki_cm_shortcode_handler( $attributes ) {
		$no_data_response = '<strong>[No Data found]</strong>';

		try {
			if ( ! is_array( $attributes ) ) {
				$attributes = array();
			}

			$defaults_attrs = array(
				'slug' => '',
				'data' => '',
			);

			$atts = shortcode_atts( $defaults_attrs, $attributes );

			$slug = $this->clean_shortcode_value( sanitize_text_field( $atts['slug'] ) );
			$data = $this->clean_shortcode_value( sanitize_text_field( $atts['data'] ) );

			if ( empty( $slug ) || empty( $data ) ) {
				return '<strong>[Slug and Data attributes are required]</strong>';
			}

			$cm_post = get_page_by_path( $slug, OBJECT, KIRKI_CONTENT_MANAGER_PREFIX );

			if ( ! $cm_post ) {
				return $no_data_response;
			}

			$data = strtolower( trim( $data ) );

			switch ( $data ) {
				case 'count': {
					return ContentManagerHelper::get_child_post_count( $cm_post->ID, array( 'publish' ) );
				}

				default:
					return $no_data_response;
			}

			return $no_data_response;
		} catch ( \Exception $e ) {
			return $no_data_response;
		}
	}

	/**
	 * Kirki shortcode handler
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function kirki_shortcode_handler( $attributes ) {
		$no_data_response = '<strong>[No Data found]</strong>';

		try {
			if ( ! is_array( $attributes ) ) {
				$attributes = array();
			}

			$defaults_attrs = array(
				'type' => '',
				'data' => '',
			);

			$atts = shortcode_atts( $defaults_attrs, $attributes );

			$type = $this->clean_shortcode_value( sanitize_text_field( $atts['type'] ) );
			$data = $this->clean_shortcode_value( sanitize_text_field( $atts['data'] ) );

			$data = strtolower( trim( $data ) );

			switch ( $type ) {
				case 'post_meta': {
					if ( is_singular() ) {
						global $post;
						$meta_value = get_post_meta( $post->ID, $data, true );
						return $meta_value;
					}
					return '';
				}

				default: {
					switch ( $data ) {
						case 'current_year': {
							return gmdate( 'Y' );
						}

						case 'current_date': {
							return gmdate( 'F j, Y' );
						}

						case 'reading_time': {
							if ( is_singular() ) {
								global $post;
								return $this->post_estimated_reading_time( $post );
							}

							return $no_data_response;
						}

						default:
							return $no_data_response;
					}

					return $no_data_response;
				}
			}

			return $no_data_response;
		} catch ( \Exception $e ) {
			return $no_data_response;
		}
	}
}
