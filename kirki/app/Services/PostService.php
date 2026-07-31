<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Post as PostModel;

class PostService 
{
    public function get_all_posts_grouped_by_type(string $search = '')
	{
		$post_types = get_post_types();
		$post_types[PostTypes::TEMPLATE] = PostTypes::TEMPLATE;
		$post_types[PostTypes::UTILITY]  = PostTypes::UTILITY;
		$post_types[PostTypes::POPUP]    = PostTypes::POPUP;

		$discarded_post_types = ['attachment', 'custom_css', 'customize_changeset', 'wp_global_styles', 'revision', 'nav_menu_item', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'];

		$post_types = array_diff_key($post_types, array_flip($discarded_post_types));

		$posts = PostModel::filter_post_type($post_types)
			->exclude_trash()
			->search($search)
			->get()
			->group_by(function ($page) {
				$group_keys = [PostTypes::TEMPLATE, PostTypes::UTILITY, PostTypes::POPUP];
				
				return in_array($page->post_type, $group_keys, true) ? PostTypes::WP_PAGE : $page->post_type;
			});

		return $posts;
	}
}