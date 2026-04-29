<?php

/**
 * Template Name: Full-width page layout
 * Template Post Type: page, post
 *
 * @package kirki
 */

use Kirki\Frontend\Preview\Preview;
use Kirki\HelperFunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_data = get_query_var('kirki_custom_data' );
$the_content = false;
$meta_tags   = false;

$template_data = HelperFunctions::get_template_data_if_current_page_is_kirki_template();
if ( $template_data ) {
	$the_content = $template_data['content'];
} else {
	// this is for Kirki utility page
	$custom_post_data = HelperFunctions::get_custom_data_if_current_page_is_kirki_custom_post();
	if ( $custom_post_data ) {
		$the_content    = $custom_post_data['content'];
		$custom_post_id = $custom_post_data['post_id'];

		$meta_tags = Preview::getSeoMetaTags( $custom_post_id );
	}
}

global $kirki_custom_header, $kirki_custom_footer; // this will set from TemplateRedirection.php class
$theme_dir = get_template_directory();
?>

<?php
if ( ! $kirki_custom_header && locate_template( 'header.php' ) ) {
	get_header();
} else {
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
	<?php
	if ( $meta_tags ) {
		echo $meta_tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</head>
<body>
	<?php } ?>
	<?php do_action( 'wp_body_open' ); ?>
	<?php
	if ( $the_content ) {
		$the_content = do_shortcode( $the_content );
		echo $the_content;
	} else {
		the_content();
	}
	?>
	<?php
	if ( ! $kirki_custom_footer && locate_template( 'footer.php' ) ) {
		get_footer();
	} else {
		wp_footer();
		?>
</body>
<?php } ?>
