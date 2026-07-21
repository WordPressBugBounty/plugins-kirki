<?php

namespace Kirki\App\Resources;

defined('ABSPATH') || exit;

use Kirki\App\Constants\OptionKeys;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Supports\Facades\Page;
use Kirki\App\Supports\PageUrl;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Resource;
use Kirki\Framework\Supports\Facades\Option;

class PageResource extends Resource
{
    public function __construct(PageModel $page)
    {
        $page->load_missing([
            'meta' => function (QueryBuilder $query) {
                $query->where_in('meta_key', PageMetaKeys::get_single_post_keys());
            }
        ]);

        parent::__construct($page);
    }
    /**
     * Convert the collection resource to an array.
     *
     * @return array The collection data as an associative array.
     */
    public function to_array()
    {
        $page_url = PageUrl::create($this->resource);

        $meta = isset($this->meta) ? $this->meta->pluck('meta_value', 'meta_key')->to_array() : [];

        $data = [
            'id' => (int) $this->ID,
            'title' => $this->post_title,
            'status' => $this->post_status,
            'post_type' => $this->post_type,
            'post_parent' => (int) $this->post_parent,
            'slug' => $this->post_name,
            'variableMode' => Page::get_variable_mode($this->resource),
            'isFrontPage' => (int) Option::get(OptionKeys::PAGE_ON_FRONT, false) === (int) $this->ID,
            'disabled_page_symbols' => $meta[PageMetaKeys::DISABLED_PAGE_SYMBOLS] ?? [],
            'staged_last_updated' => $this->get_staged_last_updated(),
            'preview_url' => $page_url->get_preview_url(),
            'editor_url' => $page_url->get_editor_url(),
        ];

        if ($this->post_type === PostTypes::UTILITY) {
            $data['utility_page_type'] = $meta[PageMetaKeys::UTILITY_PAGE_TYPE] ?? '';
        }

        if ($this->post_type === PostTypes::TEMPLATE) {
            $data['conditions'] = $meta[PageMetaKeys::TEMPLATE_CONDITIONS] ?? [];
            $data['collection_type'] = $meta[PageMetaKeys::TEMPLATE_COLLECTION_TYPE] ?? '';
        }

        if ($this->post_type === PostTypes::POPUP) {
            $data['blocks'] = $meta[PageMetaKeys::BLOCKS] ?? [];
            $data['styleBlocks'] = $meta[PageMetaKeys::STYLE_BLOCKS] ?? [];
            $data['usedFonts'] = $meta[PageMetaKeys::USED_FONT_LIST] ?? [];
        }

        return $data;
    }

    protected function get_staged_last_updated()
    {
        $published_staged_info = Page::get_published_staged_version_info((int) $this->ID);

        return isset($published_staged_info['last_updated']) ? $published_staged_info['last_updated'] : null;
    }
}
