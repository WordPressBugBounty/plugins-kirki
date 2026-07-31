<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class StagingVersionRequest extends Request
{
    public function rules()
    {
        return [
            'version_id' => 'required|integer',
        ];
    }

    public function filters()
    {
        return [
            'version_id' => Sanitizer::INT,
        ];
    }
}