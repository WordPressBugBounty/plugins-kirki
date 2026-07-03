<?php

/**
 * Validates that the given value is an email.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class EmailRule extends BaseRule
{
    /**
     * Determine if the value is an email.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \filter_var($this->value, \FILTER_VALIDATE_EMAIL);
    }
    /**
     * Get the error message for invalid email.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.email', $this->last_key_segment());
    }
}
