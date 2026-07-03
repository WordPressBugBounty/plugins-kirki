<?php

/**
 * Rule to ensure the value is after the given date.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Facades\Date;
use function Kirki\Framework\message;
class AfterRule extends BaseRule
{
    /**
     * Check if the rule is valid.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        if (\array_key_exists($this->rule_value, $this->data)) {
            if (Date::is_valid_date($this->value)) {
                return Date::is_after(Date::parse($this->value), Date::parse($this->data[$this->rule_value]));
            }
            return \false;
        }
        return \true;
    }
    /**
     * Get the error message if the rule is not valid.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.after', $this->last_key_segment(), $this->rule_value);
    }
}
