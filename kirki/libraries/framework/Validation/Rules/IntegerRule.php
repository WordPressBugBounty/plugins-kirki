<?php

/**
 * Validates that a value is an integer.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class IntegerRule extends BaseRule
{
    /**
     * Check for strict data type
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected $check_strict_data_type = \true;
    /**
     * Check if the value is a valid integer.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \filter_var($this->value, \FILTER_VALIDATE_INT) !== \false;
    }
    /**
     * Get the error message for an invalid integer value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.integer', $this->last_key_segment());
    }
}
