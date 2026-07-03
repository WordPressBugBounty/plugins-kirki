<?php

/**
 * Validates that the given value is a boolean.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class BooleanRule extends BaseRule
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
     * Determine if the value is a boolean.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        $value = $this->value;
        // Normalize string values that represent booleans
        if (\is_string($value)) {
            $value = \strtolower($value);
            return \in_array($value, ['true', 'false', '1', '0'], \true);
        }
        // Accept actual booleans and integer equivalents
        if (\is_bool($value) || $value === 1 || $value === 0) {
            return \true;
        }
        return \false;
    }
    /**
     * Get the error message for a non-boolean value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.boolean', $this->last_key_segment());
    }
}
