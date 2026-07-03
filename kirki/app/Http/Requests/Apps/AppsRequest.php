<?php

namespace Kirki\App\Http\Requests\Apps;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class AppsRequest extends Request
{
    public function rules()
    {
        return [
            'slug' => 'required|string',
            'version' => 'required|string',
            'src' => 'required|string'
        ];
    }

    public function filters()
    {
        return [
            'slug' => Sanitizer::TEXT,
            'version' => Sanitizer::TEXT,
            'src' => Sanitizer::TEXT
        ];
    }   
}