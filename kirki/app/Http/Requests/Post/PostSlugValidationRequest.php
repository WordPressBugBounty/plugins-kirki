<?php

namespace Kirki\App\Http\Requests\Post;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PostSlugValidationRequest extends Request
{
    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'post_id' => 'nullable|integer',
            'post_type' => 'required|string',
            'post_name' => 'required|string',
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'post_id' => Sanitizer::INT,
            'post_type' => Sanitizer::TEXT,
            'post_name' => Sanitizer::TEXT,
        ];
    }
}
