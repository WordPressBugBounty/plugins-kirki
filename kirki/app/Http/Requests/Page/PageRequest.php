<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostTypes;
use Kirki\App\Constants\UtilityPageType;
use Kirki\App\Services\UtilityPageService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class PageRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'blocks' => ensure_array($this->blocks),
            'conditions' => ensure_array($this->conditions),
            'custom_template' => ensure_array($this->custom_template),
        ]);
    }

    public function rules()
    {
        return [
            'post_title' => 'required|string',
            'post_type' => 'required|string',
            'blocks' => 'nullable|array',
            'conditions' => 'nullable|array',
            'collection_type' => 'nullable|string',
            'utility_page_type' => [
                'nullable',
                'string',
                'in:' . UtilityPageType::join(),
                function ($value) {
                    if ($this->post_type === PostTypes::UTILITY) {
                        if (empty($value)) {
                            return __("Utility page type is required.", 'kirki');
                        }

                        $exists = UtilityPageService::create()->exists($value);

                        if ($exists) {
                            /** translators: %s: Utility page type */
                            return sprintf(__("Utility page of type %s already exists."), $value);
                        }
                    }

                    return true;
                }
            ],
            'custom_template' => 'nullable|array',
            'custom_template.url' => 'nullable|url',
            'content_manager_collection_id' => 'nullable|integer',
            'content_manager_page_kind' => 'nullable|string|in:index,details',
        ];
    }

    public function filters()
    {
        return [
            'post_title' => Sanitizer::TEXT,
            'post_type' => Sanitizer::TEXT,
            'blocks' => Sanitizer::ARRAY,
            'conditions' => Sanitizer::ARRAY,
            'collection_type' => Sanitizer::TEXT,
            'utility_page_type' => Sanitizer::TEXT,
            'custom_template' => Sanitizer::ARRAY,
            'custom_template.url' => Sanitizer::URL,
            'content_manager_collection_id' => Sanitizer::INT,
            'content_manager_page_kind' => Sanitizer::TEXT,
        ];
    }
}
