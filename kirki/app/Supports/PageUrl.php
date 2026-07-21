<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\CollectionTypes;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Utils\PostUtil;
use Kirki\Framework\Sanitizer;

use function Kirki\App\is_not_empty_array;

class PageUrl {

    /**
     * The page
     * 
     * @var PageModel
     */
    protected $page;

    /**
     * The page permalink
     * 
     * @var string
     */
    protected $page_permalink;

    /**
     * Constructor
     * 
     * @param PageModel|int $page
     */
    public function __construct($page)
    {
        if (is_numeric($page)) {
            $page = PageModel::find($page);
        }

        $this->page = $page;
    }

    /**
     * Create a new instance
     * 
     * @param PageModel|int $page
     * @return static
     */
    public static function create($page) 
    {
        return new static($page);
    }

    /**
     * Get the page
     * 
     * @return PageModel
     */
    public function get_page()
    {
        return $this->page;
    }

    /**
     * Get the permalink of a page
     * 
     * @return string
     */
    public function get_page_permalink()
    {
        if ($this->page_permalink) {
            return $this->page_permalink;
        }

        return $this->page_permalink = $this->process_page_permalink($this->page);
    }

    /**
     * process the permalink of a page
     * 
     * @param PageModel|int $page
     * @return string
     */
    protected function process_page_permalink($page)
	{
        if (is_numeric($page)) {
            $page = PageModel::find($page);
        }

		$post_permalink = PostUtil::get_permalink($page);
		$protocol = strpos(home_url(), 'https://') !== false ? 'https' : 'http';

		if ($protocol === 'https') {
			return str_replace('http://', 'https://', $post_permalink);
		}

		return str_replace('https://', 'http://', $post_permalink);
	}

    /**
     * Get the ajax url
     * 
     * @return string
     */
    public function get_ajax_url()
    {
        $protocol = strpos(home_url(), 'https://') !== false ? 'https' : 'http';
		return admin_url('admin-ajax.php', $protocol);
    }

    public function get_preview_url()
	{
		if ($this->page->post_type === PostTypes::TEMPLATE) {
            $meta = isset($page->meta) && $page->meta->not_empty() ? $page->meta->pluck('meta_value', 'meta_key')->to_array() : [];
			$conditions = $meta[PageMetaKeys::TEMPLATE_CONDITIONS] ?? PostMeta::get_meta_value($this->page->ID, PageMetaKeys::TEMPLATE_CONDITIONS, []);

            [
                'type' => $type,
                'data' => $data
            ] = CollectionItem::get_items_from_condition($conditions);

            if (is_not_empty_array($data)) {
                return $this->get_page_permalink();
            }

            switch ($type) {
                case CollectionTypes::POST:
                    return $this->process_page_permalink($data[0]['ID']);
                case CollectionTypes::USER:
                    return get_author_posts_url($data[0]['ID']);
                case CollectionTypes::TERM:
                    return get_term_link($data[0]['ID']);
            }
		}

		return $this->get_page_permalink();
	}

    /**
     * Get the editor url of a page
     * 
     * @return string
     */
    public function get_editor_url()
    {
        $query_param = [
            'action' => KIRKI_EDITOR_ACTION
        ];

        if ($this->page->post_type === PostTypes::UTILITY) {
            $query_param['p'] = $this->page->ID;
        }

        $editor_url = add_query_arg(
            $query_param,
            $this->get_page_permalink()
        );
        
        if (EditorPreview::has_valid_token()) {
            $token = EditorPreview::get_token_from_header();

            $editor_url = add_query_arg(
                [
                    'editor-preview-token' => $token,
                ],
                $editor_url
            );
        }

        return $editor_url;
        
    }

    /**
     * Get the iframe url of a page
     * 
     * @return string
     */
    public function get_iframe_url()
    {
        $iframe_url_params = [
            'action' => KIRKI_EDITOR_ACTION,
            'load_for' => 'kirki-iframe',
            'post_id' => $this->page->ID,
        ];

        $editor_preview_token = $this->get_editor_preview_token();

        if (!empty($editor_preview_token)) {
            $iframe_url_params['editor-preview-token'] = $editor_preview_token;
        }

        return add_query_arg(
            $iframe_url_params,
            $this->get_page_permalink()
        );
    }

    /**
     * Get the nonce
     * 
     * @return string
     */
    public function get_nonce()
    {
        return wp_create_nonce('wp_rest');
    }

    /**
     * Get the editor preview token
     * 
     * @return string
     */
    public function get_editor_preview_token()
    {
        $token = filter_input(INPUT_GET, 'editor-preview-token');
        $editor_preview_token = Sanitizer::apply_rule($token, Sanitizer::TEXT);

        return !empty($editor_preview_token) ? $editor_preview_token : '';
    }

    /**
     * Get the home url
     * 
     * @return string
     */
    public function get_home_url()
    {
        return home_url();
    }

    /**
     * Get the site url
     * 
     * @return string
     */
    public function get_site_url()
    {
        return get_site_url();
    }

    /**
     * Get the admin url
     * 
     * @return string
     */
    public function get_admin_url()
    {
        return get_admin_url();
    }

    /**
     * Get the core plugin url
     * 
     * @return string
     */
    public function get_core_plugin_url()
    {
        return KIRKI_CORE_PLUGIN_URL;
    }

    /**
     * Get the rest url
     * 
     * @return string
     */
    public function get_rest_url()
    {
        return rest_url();
    }
}