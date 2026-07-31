<?php

/**
 * Conditional rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
class ConditionalRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'conditional';
    /**
     * The boolean condition for which the rules should be applied.
     *
     * @var bool|callable
     *
     * @since 1.0.0
     */
    protected $condition;
    /**
     * The rules to apply when the condition is true.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $rules;
    /**
     * The rules to apply when the condition is false.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $default_rules;
    /**
     * Create a new conditional rules instance.
     *
     * @param bool|callable $condition The boolean condition for which the rules should be applied.
     * @param array $rules The rules to apply when the condition is true.
     * @param array $default_rules The rules to apply when the condition is false.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($condition, $rules, $default_rules = [])
    {
        $this->condition = $condition;
        $this->rules = $rules;
        $this->default_rules = $default_rules;
    }
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        return \true;
    }
    /**
     * Check if the condition passes.
     *
     * @param array $data The data to check.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function passes(array $data)
    {
        return \is_callable($this->condition) ? \call_user_func($this->condition, $data) : $this->condition;
    }
    /**
     * Make the rules into an array.
     *
     * @param string|array $rules The rules to make into an array.
     *
     * @return array
     * 
     * @since 1.0.0
     */
    protected function make_array($rules)
    {
        if (\is_string($rules)) {
            return \explode('|', $rules);
        }
        return $rules;
    }
    /**
     * Get the rules.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function rules()
    {
        return $this->make_array($this->rules);
    }
    /**
     * Get the default rules.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function default_rules()
    {
        return $this->make_array($this->default_rules);
    }
    /**
     * Get the messages.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function messages()
    {
        return [];
    }
}
