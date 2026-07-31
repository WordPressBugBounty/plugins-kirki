<?php

/**
 * Unique rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Exception;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Supports\Facades\DB;
use Kirki\Framework\Validation\ValidationRule;
use function Kirki\Framework\Polyfill\array_last;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value does not already exist in a database table column.
 */
class UniqueRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'unique';
    /**
     * The constraints.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $constraints = [];
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if ($this->record_exists()) {
            return $this->fails($this->default_messages['default']);
        }
        return \true;
    }
    /**
     * Check whether a matching record already exists in the table.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function record_exists()
    {
        [$table, $column, $ignore_id] = $this->table_column_and_ignore_id();
        $query = DB::table($table)->where($column, $this->value);
        if ($ignore_id !== null) {
            $query->where('id', '!=', $ignore_id);
        }
        try {
            $query->sole();
            return \true;
        } catch (Exception $error) {
            return \false;
        }
    }
    /**
     * Resolve the prefixed table name, column, and ignored id from the rule arguments.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function table_column_and_ignore_id()
    {
        $arguments = Arr::wrap($this->args);
        $table = $arguments[0];
        $column = !empty($arguments[1]) ? $arguments[1] : array_last(\explode('.', $this->name));
        $ignore_id = $arguments[2] ?? null;
        return [$table, $column, $ignore_id];
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
