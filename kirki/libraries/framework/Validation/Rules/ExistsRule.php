<?php

/**
 * Exists rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Supports\Facades\DB;
use Kirki\Framework\Validation\ValidationRule;
use function Kirki\Framework\Polyfill\array_last;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value exists in a database table column.
 */
class ExistsRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'exists';
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if (!$this->record_exists()) {
            return $this->fails($this->default_messages['default']);
        }
        return \true;
    }
    /**
     * Check whether a matching record exists in the table.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function record_exists()
    {
        [$table, $column] = $this->table_and_column();
        $values = Arr::wrap($this->value);
        if (empty($values)) {
            return \false;
        }
        $total = DB::table($table)->where_in($column, $values)->count();
        return $total === \count($values);
    }
    /**
     * Resolve the prefixed table name and the column from the rule arguments.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function table_and_column()
    {
        $arguments = Arr::wrap($this->args);
        $table = $arguments[0];
        $column = $arguments[1] ?? array_last(\explode('.', $this->name));
        return [$table, $column];
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
