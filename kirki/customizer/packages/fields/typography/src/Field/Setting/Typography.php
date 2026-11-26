<?php
/**
 * Customizer setting for the typography field's parent setting.
 *
 * @package   kirki-framework/control-typography
 * @copyright Copyright (c) 2023, Themeum
 * @license   https://opensource.org/licenses/MIT
 * @since     6.4.4
 */

namespace Kirki\Field\Setting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps WordPress' shared multidimensional root value in sync with the parent setting.
 *
 * A typography field stores its full value in a parent setting (e.g. `my_font`)
 * and also registers one sub-setting per property (e.g. `my_font[variant]`).
 *
 * WordPress caches one root value for all settings that share a base ID. Saving a
 * sub-setting replaces its key in that cached root and writes the whole root back,
 * but saving the parent setting does not update the cache. When the parent and a
 * sub-setting are published together, the sub-setting therefore overwrites the
 * parent's new value with the stale root. Keys that only the parent carries, such
 * as `font-weight` and `font-style`, revert to their previous values.
 *
 * @since 6.4.4
 */
class Typography extends \WP_Customize_Setting {

	/**
	 * Save the value and update the shared root value before doing so.
	 *
	 * @access protected
	 * @since 6.4.4
	 * @param mixed $value The value to update.
	 * @return bool The result of saving the value.
	 */
	protected function update( $value ) {

		// A keyed parent (e.g. when using `option_name`) is already aggregated and handled by WordPress.
		if ( ! $this->is_multidimensional_aggregated ) {
			$id_base = $this->id_data['base'];

			if ( isset( self::$aggregated_multidimensionals[ $this->type ][ $id_base ] ) ) {
				self::$aggregated_multidimensionals[ $this->type ][ $id_base ]['root_value'] = $value;
			}
		}

		return parent::update( $value );

	}

}
