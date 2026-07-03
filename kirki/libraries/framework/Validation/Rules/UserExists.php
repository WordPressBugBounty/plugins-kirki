<?php

/**
 * Rule to ensure a a user exist with id.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class UserExists extends BaseRule
{
    /**
     * Check if the user exist
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        if (empty($this->value)) {
            return \true;
        }
        return get_userdata($this->value) !== \false;
    }
    /**
     * Get the error message if the post does not exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.user_exists', $this->value);
    }
}
