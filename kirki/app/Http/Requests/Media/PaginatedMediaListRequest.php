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
            'search' => 'string',
            'category' => 'string',
            'page' => 'integer|min:1',
            'limit' => 'integer',
            'sort_by' => 'string',
            'sort_order' => 'string',
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