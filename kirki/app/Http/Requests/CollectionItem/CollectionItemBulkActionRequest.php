<?php

namespace Kirki\App\Http\Requests\CollectionItem;

use Kirki\App\Constants\Collection\ActionTypes;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class CollectionItemBulkActionRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'post_ids' => ensure_array($this->post_ids),
        ]);
    }

    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'post_ids' => 'required|array',
            'post_parent' => 'required|integer',
            'action' => 'required|in:' . ActionTypes::join(), // @todo: improve later
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'post_ids' => Sanitizer::ARRAY ,
            'post_parent' => Sanitizer::INT,
            'action' => Sanitizer::TEXT,
        ];
    }
}
