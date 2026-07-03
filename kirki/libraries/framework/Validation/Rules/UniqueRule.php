<?php

/**
 * Rule to ensure that a value is unique in a specified database table and column.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Facades\DB;
use Exception;
use function Kirki\Framework\message;
class UniqueRule extends BaseRule
{
    /**
     * Check if the value unique in the specified database table and column.
     *
     * @return bool
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        if (\stripos($this->rule_value, ',') === \false) {
            throw new Exception("Missing parameters for unique rule.");
        }
        $parts = \explode(',', $this->rule_value, 3);
        $table_name = $parts[0];
        $column_name = $parts[1] ?? '';
        $id = $parts[2] ?? null;
        if (!empty($id)) {
            $result = DB::table($table_name)->where($column_name, $this->value)->where('id', '!=', $id)->first();
        } else {
            $result = DB::table($table_name)->where($column_name, $this->value)->first();
        }
        return empty($result);
    }
    /**
     * Get the error message if the row exist in DB table.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.unique', $this->last_key_segment());
    }
}
