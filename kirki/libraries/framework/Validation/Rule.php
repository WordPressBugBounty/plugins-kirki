<?php

/**
 * Rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation;

use Kirki\Framework\Contracts\Support\Arrayable;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Validation\Rules\ArrayRule;
use Kirki\Framework\Validation\Rules\BooleanRule;
use Kirki\Framework\Validation\Rules\DateRule;
use Kirki\Framework\Validation\Rules\ExistsRule;
use Kirki\Framework\Validation\Rules\FileRule;
use Kirki\Framework\Validation\Rules\InRule;
use Kirki\Framework\Validation\Rules\NotInRule;
use Kirki\Framework\Validation\Rules\NullableRule;
use Kirki\Framework\Validation\Rules\NumericRule;
use Kirki\Framework\Validation\Rules\ObjectRule;
use Kirki\Framework\Validation\Rules\ProhibitedIfRule;
use Kirki\Framework\Validation\Rules\ProhibitedRule;
use Kirki\Framework\Validation\Rules\ProhibitedUnlessRule;
use Kirki\Framework\Validation\Rules\RequiredIfRule;
use Kirki\Framework\Validation\Rules\RequiredRule;
use Kirki\Framework\Validation\Rules\RequiredUnlessRule;
use Kirki\Framework\Validation\Rules\SameAsRule;
use Kirki\Framework\Validation\Rules\SometimesRule;
use Kirki\Framework\Validation\Rules\StringRule;
use Kirki\Framework\Validation\Rules\UniqueRule;
use Kirki\Framework\Validation\Rules\ConditionalRule;
\defined('ABSPATH') || exit;
class Rule
{
    /**
     * Create a new string rule.
     *
     * @return StringRule
     *
     * @since 1.0.0
     */
    public static function string()
    {
        return new StringRule();
    }
    /**
     * Create a new email rule.
     *
     * @return StringRule
     *
     * @since 1.0.0
     */
    public static function email()
    {
        return (new StringRule())->email();
    }
    /**
     * Create a new url rule.
     *
     * @return StringRule
     *
     * @since 1.0.0
     */
    public static function url()
    {
        return (new StringRule())->url();
    }
    /**
     * Create a new regex rule.
     *
     * @param string $pattern The regex pattern.
     *
     * @return StringRule
     *
     * @since 1.0.0
     */
    public static function regex(string $pattern)
    {
        return (new StringRule())->regex($pattern);
    }
    /**
     * Create a new required rule.
     *
     * @return RequiredRule
     *
     * @since 1.0.0
     */
    public static function required()
    {
        return new RequiredRule();
    }
    /**
     * Create a new required if rule.
     *
     * @param string $other The other field to check.
     * @param mixed $value The value to check.
     *
     * @return RequiredIfRule
     *
     * @since 1.0.0
     */
    public static function required_if($other, $value = null)
    {
        return new RequiredIfRule(static::process_conditional_arguments($other, $value));
    }
    /**
     * Create a new required unless rule.
     *
     * @param string $other The other field to check.
     * @param mixed $value The value to check.
     *
     * @return RequiredUnlessRule
     *
     * @since 1.0.0
     */
    public static function required_unless($other, $value = null)
    {
        return new RequiredUnlessRule(static::process_conditional_arguments($other, $value));
    }
    /**
     * Create a new array rule.
     *
     * @return ArrayRule
     *
     * @since 1.0.0
     */
    public static function array()
    {
        return new ArrayRule();
    }
    /**
     * Create a new numeric rule.
     *
     * @return NumericRule
     *
     * @since 1.0.0
     */
    public static function numeric()
    {
        return new NumericRule();
    }
    /**
     * Create a new number rule.
     *
     * @return NumericRule
     *
     * @since 1.0.0
     */
    public static function number()
    {
        return static::numeric();
    }
    /**
     * Create a new float rule.
     *
     * @return NumericRule
     *
     * @since 1.0.0
     */
    public static function float()
    {
        return static::numeric()->float();
    }
    /**
     * Create a new integer rule.
     *
     * @return NumericRule
     *
     * @since 1.0.0
     */
    public static function integer()
    {
        return (new NumericRule())->integer();
    }
    /**
     * Create a new nullable rule.
     *
     * @return NullableRule
     *
     * @since 1.0.0
     */
    public static function nullable()
    {
        return new NullableRule();
    }
    /**
     * Create a new sometimes rule.
     *
     * @return SometimesRule
     *
     * @since 1.0.0
     */
    public static function sometimes()
    {
        return new SometimesRule();
    }
    /**
     * Create a new in rule.
     *
     * @param array|Arrayable $values The allowed values.
     *
     * @return InRule
     *
     * @since 1.0.0
     */
    public static function in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->to_array();
        }
        return new InRule(\is_array($values) ? $values : \func_get_args());
    }
    /**
     * Create a new not in rule.
     *
     * @param array|Arrayable $values The disallowed values.
     *
     * @return NotInRule
     *
     * @since 1.0.0
     */
    public static function not_in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->to_array();
        }
        return new NotInRule(\is_array($values) ? $values : \func_get_args());
    }
    /**
     * Create a new boolean rule.
     *
     * @param string|null $args The rule arguments, pass 'strict' for strict boolean checks.
     *
     * @return BooleanRule
     *
     * @since 1.0.0
     */
    public static function boolean($args = null)
    {
        return new BooleanRule($args);
    }
    /**
     * Create a new date rule.
     *
     * @return DateRule
     *
     * @since 1.0.0
     */
    public static function date()
    {
        return new DateRule();
    }
    /**
     * Create a new datetime rule.
     *
     * @return DateRule
     *
     * @since 1.0.0
     */
    public static function datetime()
    {
        return (new DateRule())->datetime();
    }
    /**
     * Create a new object rule.
     *
     * @return ObjectRule
     *
     * @since 1.0.0
     */
    public static function object()
    {
        return new ObjectRule();
    }
    /**
     * Create a new prohibited rule.
     *
     * @return ProhibitedRule
     *
     * @since 1.0.0
     */
    public static function prohibited()
    {
        return new ProhibitedRule();
    }
    /**
     * Create a new prohibited if rule.
     *
     * @param string $other The other field to check.
     * @param mixed $value The value to check.
     *
     * @return ProhibitedIfRule
     *
     * @since 1.0.0
     */
    public static function prohibited_if($other, $value = null)
    {
        return new ProhibitedIfRule(static::process_conditional_arguments($other, $value));
    }
    /**
     * Create a new prohibited unless rule.
     *
     * @param string $other The other field to check.
     * @param mixed $value The value to check.
     *
     * @return ProhibitedUnlessRule
     *
     * @since 1.0.0
     */
    public static function prohibited_unless($other, $value = null)
    {
        return new ProhibitedUnlessRule(static::process_conditional_arguments($other, $value));
    }
    /**
     * Create a new same as rule.
     *
     * @param string $field The field to match against.
     *
     * @return SameAsRule
     *
     * @since 1.0.0
     */
    public static function same_as($field)
    {
        return new SameAsRule($field);
    }
    /**
     * Create a new exists rule.
     *
     * @param string|array $table The table name, or [table, column] arguments.
     * @param string|null $column The column name, defaults to the field name.
     *
     * @return ExistsRule
     *
     * @since 1.0.0
     */
    public static function exists($table, $column = null)
    {
        return new ExistsRule(static::table_arguments($table, $column));
    }
    /**
     * Create a new unique rule.
     *
     * @param string|array $table The table name, or [table, column, ignore_id] arguments.
     * @param string|null $column The column name, defaults to the field name.
     * @param int|string|null $ignore_id The id of a record to ignore.
     *
     * @return UniqueRule
     *
     * @since 1.0.0
     */
    public static function unique($table, $column = null, $ignore_id = null)
    {
        return new UniqueRule(static::table_arguments($table, $column, $ignore_id));
    }
    /**
     * Create a new image rule.
     *
     * @return FileRule
     *
     * @since 1.0.0
     */
    public static function image()
    {
        return (new FileRule())->image();
    }
    /**
     * Create a new file rule.
     *
     * @return FileRule
     *
     * @since 1.0.0
     */
    public static function file()
    {
        return new FileRule();
    }
    /**
     * Create a new when rule.
     *
     * @param bool|callable $condition The boolean condition for which the rules should be applied.
     * @param array $rules The rules to apply when the condition is true.
     * @param array $default_rules The rules to apply when the condition is false.
     *
     * @return ConditionalRule
     *
     * @since 1.0.0
     */
    public static function when($condition, $rules, $default_rules = [])
    {
        return new ConditionalRule($condition, $rules, $default_rules);
    }
    /**
     * Create a new unless rule.
     *
     * @param bool|callable $condition The boolean condition for which the rules should be applied.
     * @param array $rules The rules to apply when the condition is false.
     * @param array $default_rules The rules to apply when the condition is true.
     *
     * @return ConditionalRule
     * 
     * @since 1.0.0
     */
    public static function unless($condition, $rules, $default_rules = [])
    {
        return new ConditionalRule($condition, $default_rules, $rules);
    }
    /**
     * Normalize table based rule arguments into a single array.
     *
     * @param string|array $table The table name or a full arguments array.
     * @param string|null $column The column name.
     * @param int|string|null $ignore_id The id of a record to ignore.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected static function table_arguments($table, $column = null, $ignore_id = null)
    {
        if (\is_array($table)) {
            return $table;
        }
        return \array_filter([$table, $column, $ignore_id], fn($argument) => $argument !== null);
    }
    /**
     * Normalize required if rule arguments into a single array.
     *
     * @param string $other The other field to check.
     * @param mixed $value The value to check.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected static function process_conditional_arguments($other, $value)
    {
        if (\is_array($other)) {
            return $other;
        }
        return \array_merge(Arr::wrap($other), Arr::wrap($value));
    }
}
