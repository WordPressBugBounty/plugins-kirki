<?php

namespace Kirki\App\Services;

use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\DTO\Page\PageSettingsDTO;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Utils\PostUtil;

defined('ABSPATH') || exit;

class PageSettingsService
{
    public static function create()
    {
        return new static();
    }

    public function update_page_settings(PageModel $page, PageSettingsDTO $payload)
    {
        $data = [
            'post_title'   => $payload->page_title,
			'post_name'    => $payload->slug,
			'post_excerpt' => $payload->page_description,
        ];

        if (!empty($payload->post_status)) {
            $data['post_status'] = $payload->post_status;
        }

        $page = PageModel::update_post($page->ID, $data);

        PostMeta::update_meta_value($page->ID, PageMetaKeys::SEO_SETTINGS, $payload->seo_settings);
        PostMeta::update_meta_value($page->ID, PageMetaKeys::CUSTOM_CODE, $payload->custom_code);
		
		$image_id = attachment_url_to_postid($payload->featured_image);

		if ($image_id > 0) {
			set_post_thumbnail($page->ID, $image_id);
		} else {
			delete_post_thumbnail($page->ID);
		}

        return $page;
    }

    public function update_page_custom_code(?array $custom_code = null)
    {
        return PostMeta::update_meta_value(PostUtil::get_post_id_from_url(), PageMetaKeys::CUSTOM_CODE, $custom_code);
    }
}