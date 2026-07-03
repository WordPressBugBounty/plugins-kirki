<?php

/**
 * Abstract base class for validation rules.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\Rule;
use function Kirki\Framework\message;
abstract class BaseRule implements Rule
{
    /**
     * Check for strict data type
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected $check_strict_data_type = \false;
    /**
     * The field key being validated.
     *
     * @var string|null
     *
     * @since 1.0.0
     */
    protected $key;
    /**
     * The value to validate.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $value;
    /**
     * The value/values for specific rule.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $rule_value;
    /**
     * All applied rules for validation.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $all_applied_rules;
    /**
     * The input data.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $data;
    /**
     * Create a new rule instance.
     *
     * @param string|null $key The field name.
     * @param mixed $value The value to validate.
     * @param mixed $rule_value The value to that can be passed to the rule.
     * @param array $data The input data.
     * @param array $all_applied_rules All applied rules
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($key = null, $value = null, $rule_value = null, $data = [], $all_applied_rules = [])
    {
        $this->key = $key;
        $this->value = $value;
        $this->rule_value = $rule_value;
        $this->data = $data;
        $this->all_applied_rules = $all_applied_rules;
    }
    /**
     * Set the field key.
     *
     * @param string $key The key.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function set_key(string $key)
    {
        $this->key = $key;
    }
    /**
     * Set the value to validate.
     *
     * @param mixed $value The value.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function set_value($value)
    {
        $this->value = $value;
    }
    /**
     * Get the value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * Get the value for the rule.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function value()
    {
        return $this->value;
    }
    /**
     * Check if the rule is for a specific data type.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_check_strict_data_type()
    {
        return $this->check_strict_data_type;
    }
    /**
     * Determine if the value is valid.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_valid()
    {
        if ($this->ignore_rule_check()) {
            return \true;
        }
        return $this->validate_rule();
    }
    /**
     * Ignore rule check if value is empty
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function ignore_rule_check()
    {
        return $this->value === null;
    }
    /**
     * Get the last key segment.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function last_key_segment()
    {
        $parts = \explode('.', $this->key);
        return $parts[\count($parts) - 1];
    }
    /**
     * Get the value for the rule.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function rule_value()
    {
        return $this->rule_value;
    }
    /**
     * Validate the rule with desired value.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public abstract function validate_rule();
    /**
     * Get the error message for this rule.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.invalid', $this->last_key_segment());
    }
}
