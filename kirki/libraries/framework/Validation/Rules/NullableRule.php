<?php

/**
 * Validates that a value is present and null.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
class NullableRule extends BaseRule
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
     * Determine if the value is present and can be null.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \is_null($this->value) || $this->value === '';
    }
    /**
     * Get the error message for a missing required field.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return '';
    }
}
