<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PageSettingsRequest extends Request
{
    public function rules()
    {
        return [
            'page_title' => 'string',
            'slug' => 'string',
            'page_description' => 'string',
            'post_status' => 'string',
            'featured_image' => 'url',
            'seo_settings' => 'array',
            'custom_code' => 'array',
        ];
    }

    public function filters()
    {
        return [
            'page_title' => Sanitizer::TEXT,
            'slug' => Sanitizer::TEXT,
            'page_description' => Sanitizer::TEXT,
            'post_status' => Sanitizer::TEXT,
            'featured_image' => Sanitizer::URL,
            'seo_settings' => Sanitizer::ARRAY,
            'custom_code' => Sanitizer::ARRAY,
        ];
    }
}