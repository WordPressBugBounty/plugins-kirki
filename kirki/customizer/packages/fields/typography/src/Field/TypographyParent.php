<?php
/**
 * Hidden parent field of the typography field.
 *
 * @package   kirki-framework/control-typography
 * @copyright Copyright (c) 2023, Themeum
 * @license   https://opensource.org/licenses/MIT
 * @since     6.4.4
 */

namespace Kirki\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The generic hidden field that holds the full typography value.
 *
 * It only differs from the generic field by its setting class.
 *
 * @since 6.4.4
 */
class TypographyParent extends Generic {

	/**
	 * The setting class.
	 *
	 * @access protected
	 * @since 6.4.4
	 * @var string
	 */
	protected $settings_class = '\Kirki\Field\Setting\Typography';

}
