<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\App\Casts\AsMetaValue;
use Kirki\Framework\Database\Query\Model;
use Throwable;

/**
 * The data of a version (a row in `kirki_stage_data`).
 *
 * Works like `PostMeta`, but a row belongs to a version (`stage_id`) instead of a post.
 * `meta_key` is the same key the published data uses in wp_postmeta, e.g. `kirki`,
 * so there are no `staged_{version}_{meta_key}` keys any more.
 *
 * @see database/migrations/CreatePageStageDataTable.php
 */
class PageStageData extends Model
{
    protected $table = 'kirki_stage_data';

    protected $primary_key = 'id';

    public $timestamps = false;

    protected $fillable = [
        'stage_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'id' => 'integer',
        'stage_id' => 'integer',
        'meta_value' => AsMetaValue::class,
    ];

    /**
     * @param int $stage_id
     * @param string $meta_key
     * @param mixed $meta_value
     *
     * @return static
     */
    public static function update_meta_value(int $stage_id, string $meta_key, $meta_value)
    {
        try {
            return static::update_or_create(
                ['stage_id' => $stage_id, 'meta_key' => $meta_key],
                ['meta_value' => $meta_value]
            );
        } catch (Throwable $throwable) {
            // Another request added this key at the same moment. Update that row instead.
            $row = static::where('stage_id', $stage_id)->where('meta_key', $meta_key)->first();

            if (!$row) {
                throw $throwable;
            }

            $row->meta_value = $meta_value;
            $row->save();

            return $row;
        }
    }

    /**
     * The version this data belongs to.
     */
    public function stage()
    {
        return $this->belongs_to(PageStage::class, 'stage_id', 'id');
    }
}
