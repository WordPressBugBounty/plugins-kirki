<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class RenameStagingVersionRequest extends Request
{
    public function rules()
    {
        return [
            'version_id' => 'required|integer',
            'name' => 'required|string',
        ];
    }

    public function filters()
    {
        return [
            'version_id' => Sanitizer::INT,
            'name' => Sanitizer::TEXT,
        ];
    }
}