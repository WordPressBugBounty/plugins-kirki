<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

/**
 * Tracks which users have seen/read a given collaboration comment,
 * stored in the `kirki_comments_seen` table.
 */
class CollaborationCommentSeen extends Model
{
    protected $table = 'kirki_comments_seen';
    protected $primary_key = 'id';
    protected $timestamps = false;

    protected $fillable = [
        'comment_id',
        'user_id',
    ];

    protected $casts = [
        'comment_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function comment()
    {
        return $this->belongs_to(CollaborationComment::class, 'comment_id', 'id');
    }
}
