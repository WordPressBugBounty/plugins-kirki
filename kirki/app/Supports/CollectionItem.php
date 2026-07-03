<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\CollectionTypes;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Models\User as UserModel;

use function Kirki\App\is_not_empty_array;
use function Kirki\Framework\user;

class CollectionItem
{
    /**
     * Get items from conditions
     * 
     * @param array $conditions
     * @param string $search
     * @return array
     */
    public static function get_items_from_condition($conditions, $search = '') 
    {
        $data = [];
		$type = CollectionTypes::POST;
		$post_type = PostTypes::WP_POST;
		$role = '*';

		if (is_not_empty_array($conditions)) {
			if (isset($conditions[0]['type'])) {
				$type = $conditions[0]['type'];

				if ($type === CollectionTypes::POST) {
					$post_type = $conditions[0]['post_type'];

					// if the conditions has from key then it is term. then it will be term type.
					if (isset($conditions[0]['from']) && $conditions[0]['from'] === CollectionTypes::TERM) {
						$type = CollectionTypes::TERM;
					}
				}

				if ($type === CollectionTypes::USER) {
					$role = $conditions[0]['to'];
				}
                
			} else {
				//legacy support
				$post_type = $conditions[0]['category'];
			}
		}

        if (user()->has_edit_access()) {
            $limit = 20;

            switch ($type) {
                case CollectionTypes::POST:
                    $posts = PostModel::query()
                        ->where('post_type', $post_type)
                        ->where_in('post_status', ['publish', 'draft', 'future'])
                        ->search($search)
                        ->order_by('ID', 'DESC')
                        ->limit($limit)
                        ->get(['ID', 'post_title']);
                    
                    foreach ($posts as $post) {
                        $data[] = [
                            'ID' => $post->ID,
                            'title' => $post->post_title,
                        ];
                    }
                    break;
                case CollectionTypes::TERM:
                    $taxonomy = [];

                    foreach ($conditions as $key => $condition) {
                        if ($condition['from'] === CollectionTypes::TERM) {
                            $taxonomy[] = $condition['where'];
                        }
                    }

                    $arg = [
                        'taxonomy' => $taxonomy,
                        'number' => $limit,
                        'orderby' => 'ID',
                        'order' => 'DESC',
                    ];

                    if ($search) {
                        $arg['search'] = $search;
                    }

                    $terms = get_terms($arg);

                    if (!is_wp_error($terms) && is_array($terms)) {
                        foreach ($terms as $term) {
                            // Handle both object and array cases safely
                            $term_id = is_object($term) ? $term->term_id : ($term['term_id'] ?? null);
                            $term_name = is_object($term) ? $term->name : ($term['name'] ?? null);

                            $data[] = [
                                'ID' => $term_id,
                                'title' => $term_name,
                            ];
                        }
                    }
                    break;
                case CollectionTypes::USER:
                    $role === '*' ? '' : $role;
                    $users = UserModel::query()
                        ->role($role)
                        ->search($search)
                        ->order_by('ID', 'DESC')
                        ->limit($limit)
                        ->get(['ID', 'display_name']);

                    foreach ($users as $user) {
                        $data[] = [
                            'ID' => $user->ID,
                            'title' => $user->display_name,
                        ];
                    }

                    break;
            }
        }

		return ['data' => $data, 'type' => $type];
    }
}