<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

/**
 * Form model — maps to the `kirki_forms` table (KIRKI_FORM_TABLE).
 *
 * One row per form element rendered on a post/page. Submitted entries are
 * stored in the related `kirki_forms_data` table via {@see FormData}.
 */
class Form extends Model
{
	/**
	 * The database table (without prefix; the connection adds it).
	 *
	 * @var string
	 */
	protected $table = 'kirki_forms';

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
		'post_id',
		'form_ele_id',
		'name',
	];

	/**
	 * Attribute casts.
	 *
	 * @var array
	 */
	protected $casts = [
		'id' => 'integer',
		'post_id' => 'integer',
	];

	/**
	 * Submitted entries for this form.
	 *
	 * @return \Kirki\Framework\Database\Query\Relations\HasMany
	 */
	public function submissions()
	{
		return $this->has_many(FormData::class, 'form_id', 'id');
	}
}
