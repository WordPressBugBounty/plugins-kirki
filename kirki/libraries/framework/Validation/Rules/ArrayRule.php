<?php

/**
 * Validates that a value is an array.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class ArrayRule extends BaseRule
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
     * Check if the value is a valid array.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        return \is_array($this->value);
    }
    /**
     * Get the error message for an invalid array value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.array', $this->last_key_segment());
    }
}
