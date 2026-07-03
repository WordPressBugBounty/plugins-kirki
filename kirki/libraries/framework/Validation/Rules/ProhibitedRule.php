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
class ProhibitedRule extends BaseRule
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
        return !isset($this->value);
    }
    /**
     * Get the error message for the prohibited field.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.prohibited', \str_replace(['_', '.'], ' ', $this->key));
    }
    /**
     * Ignore rule check.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function ignore_rule_check()
    {
        return \false;
    }
}
