<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

/**
 * A canvas/page collaboration comment, stored in the `kirki_comments` table.
 *
 * Named "CollaborationComment" (rather than "Comment") to avoid confusion
 * with WordPress's native comments feature.
 */
class CollaborationComment extends Model
{
    protected $table = 'kirki_comments';
    protected $primary_key = 'id';

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'comment',
        'meta_data',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'post_id' => 'integer',
        'parent_id' => 'integer',
        'status' => 'integer',
        'meta_data' => 'json',
        'comment' => 'json',
    ];

    /**
     * The replies of a top-level comment.
     */
    public function replies()
    {
        return $this->has_many(CollaborationComment::class, 'parent_id', 'id');
    }

    /**
     * The "seen by" rows tracking which users have read this comment.
     */
    public function seen()
    {
        return $this->has_many(CollaborationCommentSeen::class, 'comment_id', 'id');
    }
}
