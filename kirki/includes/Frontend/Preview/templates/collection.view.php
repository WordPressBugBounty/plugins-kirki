<?php

/**
 * Collection view
 *
 * @package kirki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<?php
$encoded_data = json_encode( $vars['data'] );
$attributes   = $vars['attributes'];

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '<' . esc_attr( $vars['tag'] ) . ' ' . $attributes . '>';
echo '<textarea style="display: none" ' . $attributes . '>' . esc_textarea( $encoded_data ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php foreach ( $vars['children'] as $child ) : ?>
	<?php
	/**
	 * $child is already rendered HTML and should be echoed directly.
	 */
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $child;
	?>
<?php endforeach ?>
<?php
echo '</' . esc_attr( $vars['tag'] ) . '>';
