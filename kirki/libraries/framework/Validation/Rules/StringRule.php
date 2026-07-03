<?php

/**
 * Validates that the given value is a string.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use function Kirki\Framework\message;
\defined('ABSPATH') || exit;
class StringRule extends BaseRule
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
     * Determine if the value is a string.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \is_string($this->value);
    }
    /**
     * Get the error message for a non-string value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.string', $this->last_key_segment());
    }
}
