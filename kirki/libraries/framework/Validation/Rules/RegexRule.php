<?php

/**
 * Validates that the given value with the given regex
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class RegexRule extends BaseRule
{
    /**
     * Determine if the value is valid based on the regex.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \preg_match($this->rule_value, $this->value);
    }
    /**
     * Get the error message for invalid value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.regex', $this->last_key_segment(), $this->rule_value);
    }
}
