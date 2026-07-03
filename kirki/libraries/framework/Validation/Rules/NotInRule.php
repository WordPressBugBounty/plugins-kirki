<?php

/**
 * Rule to ensure a value does not exists within a predefined set.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class NotInRule extends BaseRule
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
        $not_in = $this->rule_value;
        if (\is_string($not_in)) {
            $not_in = \str_replace(' ', '', $not_in);
            $not_in = \explode(',', $not_in);
        }
        return !\in_array($this->value, $not_in);
    }
    /**
     * Get the error message if the value is in the not allowed list.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.not_in', $this->last_key_segment(), $this->rule_value);
    }
}
