<?php

/**
 * Validates that the given value matches a specific date format.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use DateTime;
use function Kirki\Framework\message;
class DateFormatRule extends BaseRule
{
    /**
     * Determine if the value is a valid date in the given format.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        $date = DateTime::createFromFormat($this->rule_value, $this->value);
        return $date && $date->format($this->rule_value) === $this->value;
    }
    /**
     * Get the error message for invalid date format.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.date_format', $this->last_key_segment(), $this->rule_value);
    }
}
