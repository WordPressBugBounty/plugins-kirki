<?php

namespace Kirki\App\Resources;

use Kirki\App\DTO\Symbol\SymbolDTO;
use Kirki\App\Supports\Facades\Symbol;
use Kirki\Framework\Resource;

class SymbolResource extends Resource
{
    public function __construct(SymbolDTO $symbol)
    {
        parent::__construct($symbol);
    }
    
    /**
     * Convert the collection resource to an array.
     *
     * @return array The collection data as an associative array.
     */
    public function to_array()
    {
		return [
            'id' => (int) $this->id,
            'symbolData' => $this->symbolData ?? [],
            'type' => $this->type ?? 'other',
            'setAs' => $this->setAs ?? '',
            'preview' => Symbol::get_preview_html($this->resource, [], false),
        ];
    }
}
