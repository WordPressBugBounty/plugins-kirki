<?php

namespace Kirki\App\Http\Requests\CollaborationComment;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class CollaborationCommentResolveRequest extends Request
{
    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'id' => 'required|integer',
            'post_id' => 'required|integer',
            'status' => 'nullable|integer|in:1,2',
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
            'status' => Sanitizer::INT,
            'session_id' => Sanitizer::TEXT,
        ];
    }
}
