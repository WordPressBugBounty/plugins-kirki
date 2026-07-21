<?php

namespace Kirki\App\Resources;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Supports\Facades\Page;
use Kirki\App\Supports\PageUrl;
use Kirki\App\Utils\PostUtil;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Resource;

class PageContentResource extends Resource
{
    /** @var string */
    protected $stage_block_key;

    /** @var int|false */
    protected $stage_version = false;

    /**
     * PageContentResource constructor.
     * 
     * @param PageModel $page
     * @param int|false $stage_version
     */
    public function __construct(PageModel $page, $stage_version = false)
    {
        $page_id = $page->ID;

        if (!$stage_version) {
            $stage_version = Page::get_most_recent_stage_version($page_id, false);
        }

        $this->stage_version = $stage_version;

        $this->stage_block_key = Page::get_staged_meta_name(PageMetaKeys::BLOCKS, $page_id, $stage_version);

        $page->load_missing([
            'meta' => function (QueryBuilder $query) use ($page_id) {
                $query->where_in('meta_key', [
                    PageMetaKeys::BLOCKS,
                    $this->stage_block_key
                ]);
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

        $content = get_the_content(null, false, PostUtil::get_wp_post($this->resource));

        $blocks = !empty($meta[PageMetaKeys::BLOCKS]) ? $meta[PageMetaKeys::BLOCKS] : [];

        if ($this->stage_version) {
            $blocks = !empty($meta[$this->stage_block_key]) ? $meta[$this->stage_block_key] : [];
        }

        return [
            'blocks' => !empty($blocks['blocks']) ? $blocks['blocks'] : null,
            'content_length' => strlen($content),
            'is_kirki_editor_mode' => Page::is_kirki_editor_mode($this->ID),
            'preview_url' => $page_url->get_preview_url(),
            'styles' => Page::get_page_styleblocks($this->ID, $this->stage_version),
            'version_info' => Page::get_staged_version_info($this->ID, $this->stage_version),
        ];
    }
}
