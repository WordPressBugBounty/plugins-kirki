<?php

/**
 * Validates that the given value is a valid url.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class UrlRule extends BaseRule
{
    /**
     * Determine if the value is a url.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \filter_var($this->value, \FILTER_VALIDATE_URL) !== \false;
    }
    /**
     * Get the error message for invalid url.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.url', $this->last_key_segment());
    }
}
