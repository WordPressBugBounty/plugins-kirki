<?php

namespace Kirki\App\Models;

use Kirki\Framework\Database\Query\Model;

/**
 * Reference model for the `kirki_cm_reference` table.
 *
 * Stores the relationship between a content manager item (`post_id`),
 * the field it belongs to (`field_meta_key`) and the referenced post
 * (`ref_post_id`). Replaces the raw `$wpdb` access used by the legacy
 * ContentManagerHelper.
 *
 * @see database/migrations/CreateCmReferenceTable.php
 */
class Reference extends Model
{
    protected $table = 'kirki_cm_reference';
    protected $primary_key = 'id';
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'field_meta_key',
        'ref_post_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'ref_post_id' => 'integer',
    ];

    /**
     * The post that owns the reference row.
     */
    public function post()
    {
        return $this->belongs_to(Post::class, 'post_id', 'ID');
    }

    /**
     * The referenced post.
     */
    public function ref_post()
    {
        return $this->belongs_to(Post::class, 'ref_post_id', 'ID');
    }
}
