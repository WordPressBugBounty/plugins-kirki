<?php

/**
 * Object rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value is an object or an associative array.
 */
class ObjectRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'object';
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if (\is_object($this->value) || $this->is_associative_array($this->value)) {
            return \true;
        }
        return $this->fails($this->default_messages['default']);
    }
    /**
     * Check whether the value is a non-empty associative array.
     *
     * @param mixed $value The value to check.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_associative_array($value)
    {
        if (!\is_array($value) || $value === []) {
            return \false;
        }
        return \array_keys($value) !== \range(0, \count($value) - 1);
    }
    /**
     * Get the error messages.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function messages()
    {
        return $this->process_messages($this->messages);
    }
}
