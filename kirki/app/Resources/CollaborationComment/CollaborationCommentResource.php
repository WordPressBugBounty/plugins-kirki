<?php

namespace Kirki\App\Resources\CollaborationComment;

use DateTimeInterface;
use Kirki\Framework\Constants\DateTimeFormats;
use Kirki\Framework\Resource;
use function Kirki\Framework\user;

/**
 * Presentation for a collaboration comment (`CollaborationComment`).
 *
 * Mirrors the legacy `format_single_comment()` behaviour, including its
 * asymmetry: a top-level/single comment includes a `read` flag (derived from
 * the `seen` relation, eager-loaded and filtered to the current user), while
 * comments nested under `replies` never include `read` at all, since the
 * legacy replies query never joined the seen table either.
 */
class CollaborationCommentResource extends Resource
{
    /**
     * Convert the top-level/single comment resource to an array.
     * 
     * @return array<string, mixed>
     */
    public function to_array()
    {
        return array_merge($this->base_array(), [
            'read' => $this->is_read() ? 1 : 0,
        ]);
    }

    /**
     * The fields shared by both top-level comments and nested replies.
     * 
     * @return array
     */
    protected function base_array()
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'post_id' => (int) $this->post_id,
            'parent_id' => (int) $this->parent_id,
            'comment' => $this->comment,
            'meta_data' => $this->meta_data,
            'status' => (int) $this->status,
            'created_at' => $this->format_datetime($this->created_at),
            'updated_at' => $this->format_datetime($this->updated_at),
            'replies' => $this->format_replies(),
            'user_avatar' => user($this->user_id)->get_avatar(),
            'user_name' => user($this->user_id)->get_display_name(),
        ];
    }

    /**
     * Nested replies never carry a `read` flag, matching legacy behaviour.
     * 
     * @return array
     */
    protected function format_replies()
    {
        $replies = [];

        foreach ($this->replies ?? [] as $reply) {
            $replies[] = (new static($reply))->base_array();
        }

        return $replies;
    }

    /**
     * Whether the eager-loaded `seen` relation (filtered to the current user) is non-empty.
     * 
     * @return bool
     */
    protected function is_read()
    {
        return !empty($this->seen);
    }

    /**
     * Whether the eager-loaded `seen` relation (filtered to the current user) is non-empty.
     * 
     * @return mixed
     */
    protected function format_datetime($datetime)
    {
        if (is_string($datetime)) {
            return $datetime;
        }

        if ($datetime instanceof DateTimeInterface) {
            return $datetime->format(DateTimeFormats::DB_DATETIME);
        }

        return null;
    }
}
