<?php
namespace KirkiComponentLib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShowUserMetadata {
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'show_custom_user_metadata' ) );
		add_action( 'edit_user_profile', array( $this, 'show_custom_user_metadata' ) );
		add_action( 'personal_options_update', array( $this, 'save_custom_user_metadata' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_metadata' ) );
	}

	private function get_meta_list( $user_id ) {
		$meta_list = array();
		$metadata  = get_user_meta( $user_id );
		$meta_list = array();
		foreach ( $metadata as $name => $value ) {
			if ( str_starts_with( $name, KIRKI_COMPONENT_LIBRARY_APP_PREFIX ) ) {
				$meta_list[ substr( $name, strlen( KIRKI_COMPONENT_LIBRARY_APP_PREFIX ) + 1 ) ] = $value[0];
			}
		}
		return $meta_list;
	}

	private function transform_key( $string ) {
		$string = str_replace( '_', ' ', $string );
		$string = ucwords( $string );
		return $string;
	}

	public function show_custom_user_metadata( $user ) {
		$meta_list = $this->get_meta_list( $user->ID );
		if ( ! count( $meta_list ) ) {
			return;
		}
		$can_edit = current_user_can( 'edit_user', $user->ID );
		?>
<h3>Kirki User Information</h3>
<table class="form-table">
		<?php
		foreach ( $meta_list as $meta_key => $meta_value ) :
			?>
	<tr>
		<th><label
				for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $this->transform_key( $meta_key ) ); ?></label>
		</th>
		<td>
			<input <?php echo $can_edit ? '' : 'disabled'; ?> type="text"
				name="custom_meta[<?php echo esc_attr( $meta_key ); ?>]" id="<?php echo esc_attr( $meta_key ); ?>"
				value="<?php echo esc_attr( $meta_value ); ?>" class="regular-text" />
		</td>
	</tr>
	<?php endforeach; ?>
</table>
		<?php
	}


	public function save_custom_user_metadata( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// PHPCS:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['custom_meta'] ) || ! is_array( $_POST['custom_meta'] ) ) {
			return false;
		}

		$meta_list = $this->get_meta_list( $user_id );
    // PHPCS:ignore PHPCS(WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$custom_meta = wp_unslash( $_POST['custom_meta'] );
		foreach ( $meta_list as $meta_key => $meta_label ) {
			if ( ! isset( $custom_meta[ $meta_key ] ) ) {
				continue;
			}

			$value = $custom_meta[ $meta_key ];

			if ( strpos( $meta_key, 'address' ) !== false || strlen( $value ) > 50 ) {
				$meta_value = sanitize_textarea_field( $value );
			} else {
				$meta_value = sanitize_text_field( $value );
			}
				update_user_meta( $user_id, KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '_' . $meta_key, $meta_value );
		}
	}
}