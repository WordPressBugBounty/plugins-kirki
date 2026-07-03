<?php

/**
 * Validates that the given value is a float.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class FloatRule extends BaseRule
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
     * Determine if the value is a float.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return !(\filter_var($this->value, \FILTER_VALIDATE_FLOAT) === \false);
    }
    /**
     * Get the error message for a non-float value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.float', $this->last_key_segment());
    }
}
