<?php

namespace Kirki\App\Http\Requests\CollaborationComment;

use Kirki\App\Constants\CollaborationComment\CommentSegmentTypes;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class CollaborationCommentStoreRequest extends Request
{
    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'comment' => 'required|array|min:1',
            'comment.*.type' => 'required|string|in:' .  CommentSegmentTypes::join(),
            'comment.*.value' => 'nullable',
            'post_id' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'status' => 'nullable|integer',
            'meta_data' => 'nullable|array',
            'meta_data.kirki_element_id' => 'nullable|string|max:191',
            'meta_data.position' => 'nullable|array',
            'meta_data.position.x' => 'nullable|number',
            'meta_data.position.y' => 'nullable|number',
            'session_id' => 'nullable|string',
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'comment' => Sanitizer::ARRAY ,
            'comment.*.type' => Sanitizer::TEXT,
            'post_id' => Sanitizer::INT,
            'parent_id' => Sanitizer::INT,
            'status' => Sanitizer::INT,
            'meta_data' => Sanitizer::ARRAY ,
            'meta_data.kirki_element_id' => Sanitizer::TEXT,
            'meta_data.position.x' => Sanitizer::DOUBLE,
            'meta_data.position.y' => Sanitizer::DOUBLE,
            'session_id' => Sanitizer::TEXT,
        ];
    }
}
