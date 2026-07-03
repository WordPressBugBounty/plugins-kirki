<?php

namespace Kirki\App\Http\Requests;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class DownloadGoogleFontRequest extends Request
{
    public function rules()
    {
        return [
            'font' => 'required|array',
            'font.family' => 'required|string',
            'font.fontUrl' => [
                'required',
                'url',
                function ($value) {
                    if (stripos($value, 'fonts.googleapis.com') === false) {
                        return __('It\'s not a valid Google Font', 'kirki');
                    }

                    return true;
                }
            ],
            'font.files' => 'required|array',
        ];
    }

    public function filters()
    {
        return [
            'font' => Sanitizer::ARRAY,
            'font.family' => Sanitizer::TEXT,
            'font.fontUrl' => Sanitizer::URL,
            'font.files' => Sanitizer::ARRAY
        ];
    }   
}