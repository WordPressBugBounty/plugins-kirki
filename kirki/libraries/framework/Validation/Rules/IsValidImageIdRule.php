<?php

/**
 * Validates that the given value matches a specific date format.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class IsValidImageIdRule extends BaseRule
{
    /**
     * Determine if the value is a valid date in the given format.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return wp_attachment_is_image($this->value);
    }
    /**
     * Get the error message for invalid date format.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.is_valid_image_id', $this->last_key_segment());
    }
}
