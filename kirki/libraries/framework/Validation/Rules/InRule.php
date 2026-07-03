<?php

/**
 * Rule to ensure a value exists within a predefined set.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class InRule extends BaseRule
{
    /**
     * Check if the value is in the allowed list.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        $in = $this->rule_value;
        if (\is_string($in)) {
            $in = \str_replace(' ', '', $in);
            $in = \explode(',', $in);
        }
        return \in_array($this->value, $in);
    }
    /**
     * Get the error message if the value is not in the allowed list.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.in', $this->last_key_segment(), $this->rule_value);
    }
}
