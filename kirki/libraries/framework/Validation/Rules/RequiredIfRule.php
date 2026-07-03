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
class RequiredIfRule extends BaseRule
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
        list($field, $values) = \explode(',', $this->rule_value, 2);
        $values = \explode(';', $values);
        $values = \array_map(function ($value) {
            return $this->filter_value($value);
        }, $values);
        if (\array_key_exists($field, $this->data) && \in_array($this->data[$field], $values, \true)) {
            if (\is_array($this->value) && empty($this->value)) {
                return \false;
            }
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
        return message('validator.required_if', $this->last_key_segment());
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
    /**
     * Filter value.
     *
     * @param mixed $value The value.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function filter_value($value)
    {
        if ($value === 'false' || $value === 'FALSE') {
            $value = \false;
        } elseif ($value === 'true' || $value === 'TRUE') {
            $value = \true;
        } elseif ($value === 'null' || $value === 'NULL') {
            $value = null;
        }
        return $value;
    }
}
