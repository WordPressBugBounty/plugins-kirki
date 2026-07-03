<?php

/**
 * Validates that an email is unique in the WordPress users table.
 * Supports excluding current user during updates.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class EmailUniqueRule extends BaseRule
{
    /**
     * The user ID to exclude from uniqueness check (for updates).
     *
     * @var int|null
     *
     * @since 1.0.0
     */
    protected $exclude_user_id;
    /**
     * Set the user ID to exclude from uniqueness check.
     *
     * @param mixed $user_id The user id.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function exclude_user($user_id)
    {
        $this->exclude_user_id = $user_id;
        return $this;
    }
    /**
     * Determine if the email is unique in the users table.
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
        $user = get_user_by('email', $this->value);
        if ($user === \false) {
            return \true;
        }
        if ($this->exclude_user_id && $user->ID === (int) $this->exclude_user_id) {
            return \true;
        }
        return \false;
    }
    /**
     * Get the error message for a non-unique email.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.email_unique', $this->value);
    }
}
