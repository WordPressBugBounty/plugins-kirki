<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

/**
 * A version of a page (a row in `kirki_stages`).
 *
 * `type` says what the row is a version of. Only `page` for now (the column default).
 * One row per version. Replaces the old `kirki_stage_versions` post meta.
 * The version's data is in `kirki_stage_data`, see data().
 *
 * @see database/migrations/CreatePageStagesTable.php
 */
class PageStage extends Model
{
    protected $table = 'kirki_stages';

    protected $primary_key = 'id';

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'type',
        'version',
        'name',
        'edited_by',
        'is_published',
        'no_legacy_global_style',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'version' => 'integer',
        'edited_by' => 'integer',
        'is_published' => 'boolean',
        'no_legacy_global_style' => 'boolean',
    ];

    /**
     * The page this version belongs to.
     */
    public function post()
    {
        return $this->belongs_to(Post::class, 'post_id', 'ID');
    }

    /**
     * The data of this version, one row per meta key.
     */
    public function data()
    {
        return $this->has_many(PageStageData::class, 'stage_id', 'id');
    }

    /**
     * The version as the array the editor and API expect (same shape as before).
     *
     * @return array
     */
    public function to_version_array()
    {
        return [
            'version' => (int) $this->version,
            'edited_by_id' => (int) $this->edited_by,
            'edited_by' => (int) $this->edited_by,
            'created_on' => $this->created_at,
            'last_updated' => $this->updated_at,
            'name' => $this->name,
            'publish' => (bool) $this->is_published,
            'no_legacy_global_style' => (bool) $this->no_legacy_global_style,
        ];
    }
}
