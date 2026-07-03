<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Exception;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Sanitizer;

class PageDataRequest extends Request
{
    public function rules()
    {
        $rules = [
            'session_id' => 'required|string',
            'is_staging' => 'boolean',
            'data' => 'required|array',
            'data.blocks' => 'prohibited',
            'data.styles' => 'prohibited',
            'data.usedStyles' => 'prohibited',
            'data.usedStyleIdsRandom' => 'prohibited',
            'data.usedFonts' => 'prohibited',
            
        ];

        switch($this->page_content_type) {
            case 'blocks':
                $rules = array_merge($rules, [
                    'data.blocks' => 'array',
                ]);
                break;
            case 'styles':
                $rules = array_merge($rules, [
                    'data.styles' => 'array',
                ]);
                break;
            case 'used-styles':
                $rules = array_merge($rules, [
                    'data.usedStyles' => 'array',
                ]);
                break;
            case 'used-style-ids-random':
                $rules = array_merge($rules, [
                    'data.usedStyleIdsRandom' => 'array',
                ]);
                break;
            case 'used-fonts':
                $rules = array_merge($rules, [
                    'data.usedFonts' => 'array',
                ]);
                break;
            default:
                throw new Exception(__('Unknown request', 'kirki'), Response::NOT_FOUND);
        }

        return $rules;
    }

    public function filters()
    {
        return [
            'data' => Sanitizer::ARRAY,
            'data.blocks' => Sanitizer::ARRAY,
            'data.styles' => Sanitizer::ARRAY,
            'data.usedStyles' => Sanitizer::ARRAY,
            'data.usedStyleIdsRandom' => Sanitizer::ARRAY,
            'data.usedFonts' => Sanitizer::ARRAY,
            'session_id' => Sanitizer::TEXT,
            'is_staging' => Sanitizer::BOOL,
        ];
    }
}