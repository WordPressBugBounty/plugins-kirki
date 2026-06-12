<?php

namespace Kirki;

defined( 'ABSPATH' ) || exit;

/**
 * View class for HTML sanitization and escaping
 * 
 * @since 1.0.0
 */
class View {

    /**
     * Get allowed html tags
     * 
     * @return array
     */
    public static function get_allowed_html_tags() {
      $allowed_tags = wp_kses_allowed_html( 'post' );

      $form_common_attributes = [
        'id'            => true,
        'class'         => true,
        'name'          => true,
        'value'         => true,
        'title'         => true,
        'style'         => true,
        'disabled'      => true,
        'readonly'      => true,
        'required'      => true,
        'hidden'        => true,
        'tabindex'      => true,
        'accesskey'     => true,
        'autocomplete' => true,
        'autofocus'     => true,
        'form'          => true,
        'aria-label'    => true,
        'aria-hidden'   => true,
        'aria-describedby' => true,
        'data-*'        => true,
        'xml:lang'      => true,
      ];

      $input_attributes = array_merge($form_common_attributes, [
        'type'         => true,
        'placeholder'  => true,
        'checked'      => true,
        'maxlength'    => true,
        'minlength'    => true,
        'min'          => true,
        'max'          => true,
        'step'         => true,
        'pattern'      => true,
        'size'         => true,
        'multiple'     => true,
        'accept'       => true,
        'src'          => true,
        'alt'          => true,
        'list'         => true,
      ]);

      $select_attributes = array_merge($form_common_attributes, [
        'multiple' => true,
        'size'     => true,
      ]);

      $option_attributes = [
        'value'    => true,
        'selected' => true,
        'disabled' => true,
        'label'    => true,
        'data-*'   => true,
        'xml:lang' => true,
      ];

      $form_tags = [
        'form'     => [
            'action'    => true,
            'method'    => true,
            'class'     => true,
            'id'        => true,
            'data-*'   => true,
            'xml:lang' => true,
        ],
        'input'    => $input_attributes,
        'select'   => $select_attributes,
        'option'   => $option_attributes,
        'label'    => [
          'for'       => true,
          'class'     => true,
          'id'        => true,
          'style'     => true,
          'data-*'    => true,
          'xml:lang'  => true,
        ],
        'fieldset' => [
          'disabled' => true,
          'form'     => true,
          'name'     => true,
          'class'    => true,
          'id'       => true,
          'data-*'   => true,
          'xml:lang' => true,
        ],
        'legend' => [
          'class'   => true,
          'id'      => true,
          'data-*'  => true,
          'xml:lang' => true,
        ],
		  ];

      $svg_allowed_tags = [
        'svg',
        'g',
        'path',
        'circle',
        'rect',
        'line',
        'ellipse', 
        'polygon',
        'polyline',
        'text',
        'tspan',
        'defs', 
        'linearGradient',
        'radialGradient',
        'stop',
        'desc',
        'use',
        'mask'
      ];

      $svg_common_attributes = [
        'id'             => true,
        'class'          => true,
        'style'          => true,
        'fill'           => true,
        'fill-opacity'   => true,
        'fill-rule'      => true,
        'stroke'         => true,
        'stroke-width'   => true,
        'stroke-linecap' => true,
        'stroke-linejoin'=> true,
        'stroke-opacity' => true,
        'd'              => true,
        'x'              => true,
        'y'              => true,
        'width'          => true,
        'height'         => true,
        'viewBox'        => true,
        'viewbox'        => true,
        'xmlns'          => true,
        'transform'      => true,
        'mask'           => true,
        'maskUnits'      => true,
        'maskunits'      => true,
        'x1'             => true,
        'y1'             => true,
        'x2'             => true,
        'y2'             => true,
        'cx'             => true,
        'cy'             => true,
        'r'              => true,
        'rx'             => true,
        'ry'             => true,
        'points'         => true,
        'offset'         => true,
        'stop-color'     => true,
        'stop-opacity'   => true,
        'xlink:href'     => true,
      ];

		  $svg_tags = array_fill_keys($svg_allowed_tags, $svg_common_attributes);

      $extra_tags = [
        'iframe' => [
          'src'             => true,
          'width'           => true,
          'height'          => true,
          'frameborder'     => true,
          'allow'           => true,
          'allowfullscreen' => true,
          'loading'         => true,
          'title'           => true,
          'name'            => true,
          'id'              => true,
          'class'           => true,
          'style'           => true,
          'sandbox'         => true,
          'referrerpolicy'  => true,
          'scrolling'       => true,
            'importance'      => true,
            'data-*'          => true,
            'xml:lang'        => true,
          ],
          'a' => [
              'disabled' => true,
              'href' => true,
              'target' => true,
              'class' => true,
              'id' => true,
              'data-*' => true,
              'xml:lang' => true,
          ],
          'style' => [
              'type' => true,
          ],
          'script' => [
              'type' => true,
              'src' => true,
              'async' => true,
              'defer' => true,
              'integrity' => true,
              'crossorigin' => true,
              'data-*' => true,
          ],
          'img' => [
              'src' => true,
              'alt' => true,
              'width' => true,
              'height' => true,
              'srcset' => true,
              'sizes' => true,
              'loading' => true,
              'class' => true,
              'id' => true,
              'style' => true,
              'title' => true,
              'data-*' => true,
              'xml:lang' => true,
          ],
          'video' => [
              'src' => true,
              'controls' => true,
              'muted' => true,
              'autoplay' => true,
              'playsinline' => true,
              'poster' => true,
              'width' => true,
              'height' => true,
              'class' => true,
              'id' => true,
              'style' => true,
              'data-*' => true,
              'xml:lang' => true,
          ],
      ];

        // Add Kirki-specific custom attributes to all elements
      $kirki_custom_attributes = [
          'collection' => true,
          'navigation' => true,
          'pages' => true,
          'number' => true,
          'hide' => true,
          'on' => true,
      ];

        // Merge custom attributes with all tags
      foreach ( $allowed_tags as $tag => $tag_attributes ) {
          if ( is_array( $tag_attributes ) ) {
              $allowed_tags[ $tag ] = array_merge( $tag_attributes, $kirki_custom_attributes );
          }
      }

        // Also add to extra tags
      foreach ( $extra_tags as $tag => $tag_attributes ) {
          if ( is_array( $tag_attributes ) ) {
              $extra_tags[ $tag ] = array_merge( $tag_attributes, $kirki_custom_attributes );
          }
      }

      $video_tags = [
        'source' => [
          'src' => true,
          'type' => true,
          'data-*' => true,
          'xml:lang' => true,
        ]
      ];

      $allowed_tags = array_merge($allowed_tags, $svg_tags, $form_tags, $video_tags, $extra_tags);

      return $allowed_tags;
    }

    /**
     * return a string containing HTML by escaping any disallowed
     * @param string $html The HTML to render.
     * @return string
     */
    public static function safe_html($html) {
      return wp_kses($html, static::get_allowed_html_tags());
    }

	/**
	 * Safely render a string containing HTML by escaping any disallowed
	 * tags.
	 * @param string $html The HTML to render.
     * 
     * @return void
	 */
    public static function echo_safe_html($html) {
      echo wp_kses($html, static::get_allowed_html_tags());
    }
}