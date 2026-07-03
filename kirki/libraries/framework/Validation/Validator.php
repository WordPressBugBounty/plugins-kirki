<?php

/**
 * Handles data validation against defined rules.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation;

\defined('ABSPATH') || exit;
use Kirki\Framework\Validation\Constants\Validation;
use Kirki\Framework\Contracts\Rule;
use Kirki\Framework\Exceptions\InvalidValidationRuleException;
use Kirki\Framework\Exceptions\ValidationException;
use Kirki\Framework\Validation\Rules\BaseRule;
use Closure;
use InvalidArgumentException;
use function Kirki\Framework\message;
use function Kirki\Framework\value;
class Validator
{
    /**
     * The input data to validate.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $data = [];
    /**
     * The validated data after passing all rules.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $validated_data = [];
    /**
     * The validation rules for the data.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $rules = [];
    /**
     * The errors encountered during validation.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $errors = [];
    /**
     * The messages for the validation errors.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $messages = [];
    /**
     * Create a new Validator instance.
     *
     * @param array $data The input data.
     * @param array $rules The validation rules.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }
    /**
     * Static factory method for creating a Validator instance.
     *
     * @param array $data The data payload.
     * @param array $rules The rules.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function make(array $data, array $rules, array $messages = [])
    {
        return new static($data, $rules, $messages);
    }
    /**
     * Apply if we need to validate a field rules if the callback is resolved.
     *
     * @param string|array $field The fields to validate
     * @param string $rules The validation rules to applied if $callback returns true.
     * @param callable $callback The callback to check if the field should be validated
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     *
     * @since 1.0.0
     */
    public function apply_if($field, string $rules, callable $callback)
    {
        if (empty($field) || empty($rules)) {
            throw new InvalidArgumentException('Field and rules are required for sometimes validation.');
        }
        if (!\is_array($field)) {
            $field = [$field];
        }
        if (!$callback($this->data)) {
            return $this;
        }
        foreach ($field as $item) {
            $this->rules[$item] = $rules;
        }
        return $this;
    }
    /**
     * Determine if the data passed validation.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_valid()
    {
        $this->run_validation();
        return empty($this->errors);
    }
    /**
     * Determine if the validation failed
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_failed()
    {
        $this->run_validation();
        return !empty($this->errors);
    }
    /**
     * Validate the data against the defined rules.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate()
    {
        if ($this->is_failed()) {
            throw ValidationException::with_errors($this->errors);
        }
        return \true;
    }
    /**
     * Get the validated data.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function validated()
    {
        return $this->validated_data;
    }
    /**
     * Get validation errors, if any.
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function get_errors()
    {
        return !empty($this->errors) ? $this->errors : null;
    }
    /**
     * Run validation on the data using defined rules.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function run_validation()
    {
        foreach ($this->rules as $field => $field_rules) {
            if (\is_string($field_rules)) {
                $field_rules = \explode('|', $field_rules);
            }
            $key_segments = \explode('.', $field);
            $this->traverse_and_validate($this->data, $key_segments, [], $field_rules);
        }
    }
    /**
     * Recursively traverses the input data structure according to a dot-notated rule key,
     * handling wildcard segments (e.g., *) to apply validation rules at dynamic levels.
     *
     * @param mixed $current_data The current level of data being inspected. This is a portion
     * @param array $key_segments The remaining parts (split by '.') of the rule key that
     * @param array $traversed_path_stack The stack of key_segments already traversed so far. This is used
     * @param array $rules An array of validation rules to apply (e.g., ['required', 'string']).
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function traverse_and_validate($current_data, $key_segments, $traversed_path_stack, $rules)
    {
        // when all segments have been traversed
        if (empty($key_segments)) {
            $traversed_key = \implode('.', $traversed_path_stack);
            $this->apply_rules($rules, $traversed_key, $current_data);
            return;
        }
        $segment = \array_shift($key_segments);
        if ($segment === '*') {
            if (\is_null($current_data)) {
                return;
            }
            if (!\is_array($current_data)) {
                $traversed_key = \implode('.', $traversed_path_stack);
                $this->errors[$traversed_key][] = $this->resolve_expected_array_message($traversed_key);
                return;
            }
            foreach ($current_data as $index => $item) {
                $this->traverse_and_validate($item, $key_segments, \array_merge($traversed_path_stack, [$index]), $rules);
            }
        } elseif (\is_array($current_data) && \array_key_exists($segment, $current_data)) {
            $this->traverse_and_validate($current_data[$segment], $key_segments, \array_merge($traversed_path_stack, [$segment]), $rules);
        } else {
            // If the field is missing and there are still segments left,
            // it means an intermediate key is missing. We should stop here.
            // The validation for the intermediate key itself (if any) is handled by its own rule.
            if (!empty($key_segments)) {
                return;
            }
            // Field missing, still run rules to trigger "required" failure
            $traversed_key = \implode('.', \array_merge($traversed_path_stack, [$segment]));
            $this->apply_rules($rules, $traversed_key, null, \true);
        }
    }
    /**
     * Applies a set of rules to a given value.
     *
     * @param array $rules An array of validation rules to apply.
     * @param string $traversed_key The dot-notated key of the value being validated.
     * @param mixed|null $value The value being validated.
     * @param bool $is_field_missing Whether the field being validated is missing from request.
     *
     * @return void
     *
     * @throws InvalidValidationRuleException
     *
     * @since 1.0.0
     */
    protected function apply_rules($rules, $traversed_key, $value = null, $is_field_missing = \false)
    {
        $is_valid = \true;
        $strict_rule_validations = [];
        foreach ($rules as $rule) {
            if ($rule instanceof Closure) {
                $response = $rule($value, $traversed_key, $this->data);
                if ($response !== \true && !\is_string($response)) {
                    throw new InvalidValidationRuleException(\sprintf('The closure must return a boolean true for valid or string as error message, "%s" given', \is_null($response) ? 'null' : (string) $response));
                }
                if (\is_string($response)) {
                    $this->errors[$traversed_key][] = $response;
                    continue;
                }
                $is_valid = $response;
                continue;
            }
            $rule_instance = $this->get_rule_class($rule, $traversed_key, $value, $rules);
            if ($rule_instance->is_check_strict_data_type()) {
                $strict_rule_validations[$rule] = ['is_valid' => $rule_instance->is_valid(), 'message' => $this->process_error_message($rule_instance, $traversed_key), 'traversed_key' => $traversed_key];
                continue;
            }
            if (!$rule_instance->is_valid()) {
                $is_valid = \false;
                $this->errors[$traversed_key][] = $this->process_error_message($rule_instance, $traversed_key);
            }
        }
        if (!empty($strict_rule_validations)) {
            $is_valid = $this->validate_strict_rules($strict_rule_validations);
        }
        if ($is_valid && !$is_field_missing) {
            $key_segments = \explode('.', $traversed_key);
            $this->set_validated_data($key_segments, $value);
        }
    }
    /**
     * Resolve the expected array error message for a given key.
     *
     * @param string $key The key.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function resolve_expected_array_message(string $key)
    {
        if ($this->has_custom_message('expected_array', $key)) {
            return value($this->get_custom_message('expected_array', $key), $key, null, $this->data);
        }
        return message('validator.expected_array', $key);
    }
    /**
     * Process the error message for a given rule and key.
     *
     * @param Rule $rule The rule.
     * @param string $key The key.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function process_error_message(Rule $rule, string $key)
    {
        if (\is_null($rule_name = $this->rule_name_from($rule))) {
            throw new InvalidValidationRuleException(\sprintf('The rule %s does not exist or is invalid', \get_class($rule)));
        }
        if (!$this->has_custom_message($rule_name, $key)) {
            return $rule->get_error_message();
        }
        $message = $this->get_custom_message($rule_name, $key);
        return value($message, $key, $rule->rule_value(), $this->data);
    }
    /**
     * Get the rule name from a rule instance.
     *
     * @param Rule $rule The rule.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    protected function rule_name_from(Rule $rule)
    {
        $class_name = \get_class($rule);
        $rule_map = \array_flip(Validation::RULE_MAP);
        return $rule_map[$class_name] ?? null;
    }
    /**
     * Check if a custom message is defined for a given key.
     *
     * @param string $rule_name The rule name.
     * @param string $key The key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function has_custom_message(string $rule_name, string $key) : bool
    {
        return isset($this->messages[$key][$rule_name]);
    }
    /**
     * Get the custom message for a given key.
     *
     * @param string $rule_name The rule name.
     * @param string $key The key.
     *
     * @return string|callable
     *
     * @since 1.0.0
     */
    protected function get_custom_message(string $rule_name, string $key)
    {
        return $this->messages[$key][$rule_name];
    }
    /**
     * Validate strict rules.
     * check is there any rule is true then return true otherwise false and set errors
     *
     * @param array $strict_validations The strict validations.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_strict_rules(array $strict_validations)
    {
        if (\array_search(\true, \array_column($strict_validations, 'is_valid'), \true) !== \false) {
            return \true;
        }
        foreach ($strict_validations as $item) {
            if (!$item['is_valid'] && $item['message']) {
                $this->errors[$item['traversed_key']][] = $item['message'];
            }
        }
        return \false;
    }
    /**
     * Set the validated data using a series of keys to traverse the nested array structure.
     *
     * @param array $keys An array of keys representing the path in the nested array structure.
     * @param mixed $value The value to set at the specified path in the validated data array.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function set_validated_data($keys, $value)
    {
        $ref =& $this->validated_data;
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = [];
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
    }
    /**
     * Get the rule class instance.
     *
     * @param mixed $rule The rule.
     * @param mixed $key The key.
     * @param mixed $value The value.
     * @param mixed $all_applied_rules The all applied rules.
     *
     * @return Rule
     *
     * @throws InvalidValidationRuleException
     *
     * @since 1.0.0
     */
    protected function get_rule_class($rule, $key, $value, $all_applied_rules)
    {
        $rule_name_value_array = \explode(':', $rule, 2);
        $rule_name = $rule_name_value_array[0];
        $rule_value = isset($rule_name_value_array[1]) ? $rule_name_value_array[1] : null;
        if (\is_string($rule) && isset(Validation::RULE_MAP[$rule_name_value_array[0]])) {
            $class_name = Validation::RULE_MAP[$rule_name];
            return new $class_name($key, $value, $rule_value, $this->data, $all_applied_rules);
        }
        if (\class_exists($rule_name) && \is_subclass_of($rule_name, BaseRule::class)) {
            return new $rule_name($key, $value, $rule_value, $this->data, $all_applied_rules);
        }
        throw new InvalidValidationRuleException(\sprintf('The validation rule %s does not exist or is invalid', $rule_name));
    }
    /**
     * Set the error message for a given key in the errors array.
     * The key can be a dot-notated string, e.g. `user.email`.
     *
     * @param string $key The key to set the error
     * @param string $error_msg The error message
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function add_error($key, $error_msg)
    {
        $this->errors[$key] = $error_msg;
    }
}
