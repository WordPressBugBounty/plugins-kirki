<?php

namespace Kirki\App\Http\Requests\Media;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PaginatedMediaListRequest extends Request
{
    public function rules()
    {
        return [
            'search' => 'nullable|string',
            'category' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer',
            'sort_by' => 'nullable|string',
            'sort_order' => 'nullable|string',
        ];
    }

    public function filters()
    {
        return [
            'search' => Sanitizer::TEXT,
            'category' => Sanitizer::TEXT,
            'page' => Sanitizer::INT,
            'limit' => Sanitizer::INT,
            'sort_by' => Sanitizer::TEXT,
            'sort_order' => Sanitizer::TEXT,
        ];
    }
}