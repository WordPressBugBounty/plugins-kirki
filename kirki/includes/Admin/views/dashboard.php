<?php
/**
 * WP Kirki Dashboard React root renderer
 *
 * @package kirki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;

echo '<div id="' . 'kirki' . '-app"></div>';

$url_arr = HelperFunctions::get_post_url_arr_from_post_id(
	get_the_ID(),
	array(
		'ajax_url'  => true,
		'rest_url'  => true,
		'admin_url' => true,
		'site_url'  => true,
		'nonce'     => true,
	)
);

$last_edited_kirki_editor_page         = HelperFunctions::get_last_edited_kirki_editor_type_page();
$last_edited_kirki_editor_page_url_arr = array( 'editor_url' => false );
if ( $last_edited_kirki_editor_page ) {
	$last_edited_kirki_editor_page_url_arr = HelperFunctions::get_post_url_arr_from_post_id( $last_edited_kirki_editor_page->ID, array( 'editor_url' => true ) );
}
?>

<script>
window.wp_kirki =
	<?php
	echo wp_json_encode(
		array(
			'ajaxUrl'                      => esc_url( $url_arr['ajax_url'] ),
			'restUrl'                      => esc_url( $url_arr['rest_url'] ),
			'hasValidLicense'              => (bool) HelperFunctions::is_pro_user(),
			'nonce'                        => sanitize_text_field( $url_arr['nonce'] ),
			'version'                      => sanitize_text_field( KIRKI_VERSION ),
			'adminUrl'                     => esc_url( $url_arr['admin_url'] ),
			'kirkiWPDashboard'             => esc_url( site_url( '/wp-admin' ) ),
			'lastEditedKirkiEditorPageUrl' => esc_url( $last_edited_kirki_editor_page_url_arr['editor_url'] ),
		),
		JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	?>
	;
</script>
