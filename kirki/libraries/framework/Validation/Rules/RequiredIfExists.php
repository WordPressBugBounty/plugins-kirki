<?php

/**
 * Validates that a value is present and not null.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use function Kirki\Framework\message;
class RequiredIfExists extends BaseRule
{
    /**
     * Determine if the value is present.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        if (\array_key_exists($this->rule_value, $this->data) && !empty($this->data[$this->rule_value])) {
            return !\is_null($this->value) && $this->value !== '';
        }
        return \true;
    }
    /**
     * Get the error message for a missing required field.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.required_if_exists', $this->last_key_segment());
    }
    /**
     * Ignore rule check.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function ignore_rule_check()
    {
        return \false;
    }
}
