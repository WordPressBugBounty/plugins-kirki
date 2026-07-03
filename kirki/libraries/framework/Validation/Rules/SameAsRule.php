<?php

/**
 * Validates that a value is same as another specified field.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class SameAsRule extends BaseRule
{
    /**
     * Determine if the value is matched.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        $required_value = $this->data[$this->rule_value] ?? null;
        return !\is_null($this->value) && $this->value === $required_value;
    }
    /**
     * Get the error message for a missing field.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.same_as', $this->last_key_segment(), $this->rule_value);
    }
}
