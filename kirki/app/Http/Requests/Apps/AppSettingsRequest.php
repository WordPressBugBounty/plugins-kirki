<?php

namespace Kirki\App\Http\Requests\Apps;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class AppSettingsRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'settings' => ensure_array($this->settings),
        ]);
    }

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