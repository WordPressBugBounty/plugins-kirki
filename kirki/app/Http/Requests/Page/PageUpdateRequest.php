<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PageUpdateRequest extends Request
{
    public function rules()
    {
        return [
            'post_title' => 'string',
            'post_name' => 'string',
            'post_status' => 'string',
        ];
    }

    public function filters()
    {
        return [
            'post_title' => Sanitizer::TEXT,
            'post_name' => Sanitizer::TEXT,
            'post_status' => Sanitizer::TEXT,
        ];
    }
}