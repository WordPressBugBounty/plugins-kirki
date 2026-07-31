<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class PageSettingsRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'seo_settings' => ensure_array($this->seo_settings),
            'custom_code' => ensure_array($this->custom_code),
        ]);
    }

    public function rules()
    {
        return [
            'page_title' => 'nullable|string',
            'slug' => 'nullable|string',
            'page_description' => 'nullable|string',
            'post_status' => 'nullable|string',
            'featured_image' => 'nullable|url',
            'seo_settings' => 'nullable|array',
            'custom_code' => 'nullable|array',
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