<?php

/**
 * Validates that a value is an object.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class ObjectRule extends BaseRule
{
    /**
     * Check if the value is a valid object.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \is_object($this->value);
    }
    /**
     * Get the error message for an invalid object value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.object', $this->last_key_segment());
    }
}
