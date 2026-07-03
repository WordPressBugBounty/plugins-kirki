<?php

namespace Kirki\App\Services;

use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostStatus;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Canvas;

defined('ABSPATH') || exit;

class EditorService
{
    public function back_to_kirki_editor(PageModel $page, ?string $new_title = null)
    {
		if ($page->post_status === PostStatus::AUTO_DRAFT) {
			if (empty($new_title)) {
				$new_title = 'Untitled';
			}

			$data = [
                'ID' => $page->ID,
				'post_title' => $new_title,
				'post_name' => $new_title,
				'post_status' => PostStatus::DRAFT,
            ];

            $page = PageModel::update_post($page->ID, $data);
		}

        PostMeta::update_meta_value($page->ID, PageMetaKeys::PAGE_TEMPLATE, Canvas::get_full_canvas_template_path());

        return $page;
    }

    public function back_to_wordpress_editor(PageModel $page)
    {
        $is_deleted = PostMeta::query()
            ->where('post_id', $page->ID)
            ->where('meta_key', PageMetaKeys::EDITOR_MODE)
            ->delete();

        return !empty($is_deleted);
    }
}