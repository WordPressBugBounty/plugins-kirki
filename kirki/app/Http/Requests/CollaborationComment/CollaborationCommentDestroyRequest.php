<?php

namespace Kirki\App\Http\Requests\CollaborationComment;

use Kirki\App\Constants\AccessLevels;
use Kirki\App\Models\CollaborationComment;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;
use function Kirki\Framework\user;

class CollaborationCommentDestroyRequest extends Request
{
    /**
     * Validate permissions.
     *
     * @return bool
     */
    public function authorize()
    {
        $comment = CollaborationComment::find($this->get_int('id'));

        if (!$comment) {
            return false;
        }

        $is_author = $comment->user_id === user()->get_id();

        return $is_author || user()->has_access(AccessLevels::FULL_ACCESS);
    }

    /**
     * Validation rules.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|integer',
            'post_id' => 'required|integer',
            'session_id' => 'nullable|string',
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'id' => Sanitizer::INT,
            'post_id' => Sanitizer::INT,
            'session_id' => Sanitizer::TEXT,
        ];
    }
}
