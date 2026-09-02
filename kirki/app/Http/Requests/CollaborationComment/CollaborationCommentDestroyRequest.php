<?php

namespace Kirki\App\Http\Requests\CollaborationComment;

use Kirki\App\Services\CollaborationCommentService;
use Kirki\Framework\Container;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class CollaborationCommentDestroyRequest extends Request
{
    /**
     * Validate permissions.
     *
     * @return bool
     */
    public function authorize()
    {
        return Container::get_instance()
            ->make(CollaborationCommentService::class)
            ->can_manage_comment($this->get_int('id'));
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
