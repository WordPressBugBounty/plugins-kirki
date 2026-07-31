<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

/**
 * Form submission data model — maps to the `kirki_forms_data` table
 * (KIRKI_FORM_DATA_TABLE).
 *
 * Stores one row per submitted field. All rows belonging to a single
 * submission share the same unix `timestamp` and `session_id`.
 */
class FormData extends Model
{
	/**
	 * The database table (without prefix; the connection adds it).
	 *
	 * @var string
	 */
	protected $table = 'kirki_forms_data';

	/**
	 * The primary key column.
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * created_at / updated_at are handled by the table's CURRENT_TIMESTAMP
	 *
	 * @var bool
	 */
	protected $timestamps = true;

	/**
	 * Mass-assignable attributes.
	 *
	 * @var array
	 */
	protected $fillable = [
		'form_id',
		'user_id',
		'session_id',
		'timestamp',
		'input_key',
		'input_value',
		'input_type',
	];
}
