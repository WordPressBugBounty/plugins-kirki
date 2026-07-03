<?php

namespace Kirki\App\Http\Requests\Apps;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class AppSettingsRequest extends Request
{
    public function rules()
    {
        return [
            'slug' => 'required|string',
            'settings' => 'required|array'
        ];
    }

    public function filters()
    {
        return [
            'slug' => Sanitizer::TEXT,
            'settings' => Sanitizer::ARRAY
        ];
    }   
}