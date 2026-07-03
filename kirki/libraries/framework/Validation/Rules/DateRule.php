<?php

/**
 * Validates that the given value matches db date format.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use Kirki\Framework\Constants\DateTimeFormats;
use DateTime;
use function Kirki\Framework\message;
class DateRule extends BaseRule
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
        $date = DateTime::createFromFormat(DateTimeFormats::DB_DATE, $this->value);
        return $date && $date->format(DateTimeFormats::DB_DATE) === $this->value;
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
        return message('validator.date', $this->last_key_segment(), DateTimeFormats::DB_DATE);
    }
}
