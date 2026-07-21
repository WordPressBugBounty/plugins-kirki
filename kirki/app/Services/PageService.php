<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\PageContentTypes;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\DTO\Page\EditorPagePayloadDTO;
use Kirki\App\DTO\Page\EditPageDTO;
use Kirki\App\DTO\Page\EditPopupDTO;
use Kirki\App\DTO\Page\PagePayloadDTO;
use Kirki\App\DTO\Page\TogglePageSymbolDTO;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Canvas;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\App\Supports\Facades\Page;
use Kirki\App\Supports\Template;
use Kirki\Framework\Http\Response;

use function Kirki\App\soft_flush_rewrite_rules;
use function Kirki\Framework\collection;

class PageService 
{
	/**
	 * Create a new page.
	 * 
	 * @param PagePayloadDTO $payload
	 * 
	 * @return PageModel
	 */
	public function save(PagePayloadDTO $payload)
	{
		$page_data = [
			'post_title' => $payload->post_title,
			'post_name'  => $payload->post_title,
			// Check if type = kirki_page then change it to wp page type. cause we only set template if page type is kirki_page.
			'post_type' => $payload->post_type === PostTypes::PAGE ? PostTypes::WP_PAGE : $payload->post_type,
		];

		$page = PageModel::create_post($page_data);

		if (!empty($payload->blocks)) {
			// This is for popup creation. cause popup has predefined blocks.
			Page::save_blocks($page->ID, $payload->blocks);
		}

		if (!empty($payload->conditions)) {
			// This is for template creation. cause popup has predefined conditions.
			PostMeta::update_meta_value($page->ID, PageMetaKeys::TEMPLATE_CONDITIONS, $payload->conditions);
		}

		// @todo: Need to remove this code. after checking collection_type used or not
		if (!empty($payload->collection_type)) {
			PostMeta::update_meta_value($page->ID, PageMetaKeys::TEMPLATE_COLLECTION_TYPE, $payload->collection_type);
		}

		if (!empty($payload->utility_page_type)) {
			PostMeta::update_meta_value($page->ID, PageMetaKeys::UTILITY_PAGE_TYPE, $payload->utility_page_type);
		}

		Page::save_editor_mode($page->ID);

		switch ($payload->post_type) {
			case PostTypes::WP_PAGE:
			case PostTypes::PAGE:
				PostMeta::update_meta_value($page->ID, PageMetaKeys::PAGE_TEMPLATE, Canvas::get_full_canvas_template_path());
				break;
			case PostTypes::UTILITY:
				if (!empty($payload->utility_page_type)) {
					Template::assign_utility_page_template($page->ID, $payload->utility_page_type);
					soft_flush_rewrite_rules();
				}
				break;
			default:
				break;
		}

		if (
			!empty($payload->custom_template) 
			&& isset($payload->custom_template['url']) 
			&& !empty(($payload->custom_template['url']))
		) {
			Template::assign_custom_page_template($page->ID, $payload->custom_template['url']);
		}

		return $page;
	}

	/**
	 * Update page data
	 * 
	 * @param PageModel $page
	 * @param EditPageDTO $payload
	 * 
	 * @return PageModel
	 */
	public function update(PageModel $page, EditPageDTO $payload)
	{
		$post_payload = collection($payload->to_array())->filter(fn ($value) => !is_null($value))->to_array();

		if (!empty($post_payload)) {
			$page = PageModel::update_post($page->ID, $post_payload);

			if ($page->post_type === PostTypes::UTILITY) {
				soft_flush_rewrite_rules();
        	}
		}

		return $page;
	}

	public function update_popup_data(PageModel $popup, EditPopupDTO $payload)
	{
		if (!is_null($payload->blocks)) {
			Page::save_blocks($popup->ID, $payload->blocks);
		}

		if (!is_null($payload->styleBlocks)) {
			Page::save_style_blocks($popup->ID, $payload->styleBlocks);
		}

		if (!is_null($payload->usedFonts)) {
			Page::save_used_font_list($popup->ID, $payload->usedFonts);
		}

		return $popup;
	}

    /**
     * Save editor page data
     * 
     * @return int|false - when not staging return false otherwise return staging version
     */
    public function save_page_data(EditorPagePayloadDTO $payload, string $page_content_type) 
    {
        if (is_null($payload->page) || empty($payload->data)) {
            return false;
        }

		Page::save_editor_mode($payload->page->ID);

        $staging_version = false;
        $page_data = $payload->data;

		if($payload->is_staging) {
			$staging_version = Page::get_most_recent_stage_version($payload->page->ID);
			Page::set_last_edited_datetime_of_stage_version($payload->page->ID);
		}

		switch ($page_content_type) {
			case PageContentTypes::BLOCKS:
				Page::save_blocks($payload->page->ID, ['blocks' => $page_data['blocks'] ?? []], $staging_version);
				break;
			case PageContentTypes::STYLES:
				$styles = $page_data['styles'] ?? [];
				Page::save_style_blocks($payload->page->ID, $styles, $staging_version);
				break;
			case PageContentTypes::USED_STYLES:
				Page::save_used_global_style_block_ids($payload->page->ID, $page_data['usedStyles'] ?? [], $staging_version);
				break;
			case PageContentTypes::USED_STYLE_IDS_RANDOM:
				Page::save_used_style_block_ids($payload->page->ID, $page_data['usedStyleIdsRandom'] ?? [], $staging_version);
				break;
			case PageContentTypes::USED_FONTS:
				Page::save_used_font_list($payload->page->ID, $page_data['usedFonts'] ?? [], $staging_version);
				break;
			case PageContentTypes::CUSTOM_FONTS:
				// Save others data if isset. this is only used for template import
				if (!isset($page_data['customFonts'])) {
					break;
				}
				
				$custom_fonts = GlobalData::get_global_custom_fonts();

				foreach ($page_data['customFonts'] as $key => $custom_font) {
					$custom_fonts[$key] = $custom_font;
				}

				GlobalData::update_global_custom_fonts($custom_fonts);
				break;
			case PageContentTypes::VIEWPORT_LIST:
				// Save others data if isset. this is only used for template import
				if (!isset($page_data['viewportList'])) {
					break;
				}

				$controller_data = GlobalData::get_global_ui_controller();

				if (!$controller_data) {
					$controller_data = [
						'viewport' => [
							'active' => 'md',
							'defaults'=> ["md", "tablet", "mobileLandscape", "mobile"],
							'list' => $page_data['viewportList'],
							'mdWidth' => 1200,
							"scale" => 1,
							"width" => 2484,
							"zoom" => 1
						]
					];
				}

				$controller_data['viewport']['list'] = $page_data['viewportList'];

				GlobalData::update_global_ui_controller($controller_data);
				break;
		}

		return $staging_version;
    }

	/**
	 * Toggle disabled page symbols
	 * 
	 * @param TogglePageSymbolDTO $payload
	 * 
	 * @return bool
	 */
	public function toggle_disabled_page_symbols(TogglePageSymbolDTO $payload)
	{
		$prev_status = PostMeta::get_meta_value($payload->post_id, PageMetaKeys::DISABLED_PAGE_SYMBOLS, []);

		if (!is_array($prev_status)) {
			$prev_status = [];
		}

		$current_status = array_merge($prev_status, [$payload->symbol_type => $payload->disable]);

		PostMeta::update_meta_value($payload->post_id, PageMetaKeys::DISABLED_PAGE_SYMBOLS, $current_status);

		return true;
	}

	/**
	 * Duplicate page
	 * 
	 * @param PageModel $current_page
	 * 
	 * @return PageModel New page
	 */
	public function duplicate_page(PageModel $current_page)
	{
		$new_page = PageModel::create_post([
			'post_title'   => $current_page->post_title . ' (copy)',
			'post_content' => $current_page->post_content,
			'post_name'    => $current_page->post_name,
			'post_type'    => $current_page->post_type,
			'post_status'  => $current_page->post_status,
		]);

		$current_page_block = PostMeta::get_meta_value($current_page->ID, PageMetaKeys::BLOCKS);

		if (!empty($current_page_block)) {
			Page::save_blocks($new_page->ID, $current_page_block);
			Page::save_editor_mode($new_page->ID);
		}

		/**
		 * Also duplicate _wp_page_template meta if exists
		 */
		$current_page_template = PostMeta::get_meta_value($current_page->ID, PageMetaKeys::PAGE_TEMPLATE);

		if (!empty($current_page_template)) {
			$current_page_template = Canvas::normalize_full_canvas_template_path($current_page_template);
			PostMeta::update_meta_value($new_page->ID, PageMetaKeys::PAGE_TEMPLATE, $current_page_template);
		}

		/**
		 * Also duplicate this page style blocks if exists
		 */
		$current_post_styles = PostMeta::get_meta_value($current_page->ID, PageMetaKeys::STYLE_BLOCKS, []);

		if (!empty($current_post_styles)) {
			Page::save_style_blocks($new_page->ID, $current_post_styles);
		}

		$current_used_fonts = PostMeta::get_meta_value($current_page->ID, PageMetaKeys::USED_FONT_LIST, []);

		if (!empty($current_used_fonts)) {
			Page::save_used_font_list($new_page->ID, $current_used_fonts);
		}

		if ($current_page->post_type === PostTypes::UTILITY) {
			soft_flush_rewrite_rules();
		}

		return $new_page;
	}

	public function delete_page(PageModel $page) {
		$is_deleted = PageModel::delete_post($page->ID);

        if (!$is_deleted) {
            throw new Exception(esc_html__('Page not deleted.', 'kirki'), Response::FORBIDDEN);
        }

        if ($page->post_type === PostTypes::UTILITY) {
            soft_flush_rewrite_rules();
        }

		return $is_deleted;
	}
}