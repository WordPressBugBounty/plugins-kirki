<?php

namespace Kirki\App\Traits;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostStatus;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Supports\Arr;

trait SearchablePost {
    /**
     * Search posts
     * 
     * @param QueryBuilder $query
     * @param string $keyword
     * @return QueryBuilder
     */
    public function scope_search(QueryBuilder $query, $keyword) {
        if (!is_string($keyword) || $keyword === '') {
            return $query;
        }

        return $query->where(function (QueryBuilder $query) use ($keyword) {
                $query->where_like('post_title', $keyword . '%')
                    ->or_where_like('post_content', $keyword . '%')
                    ->or_where_like('post_excerpt', $keyword . '%');
            });
    }

    /**
     * Filter posts by status
     * 
     * @param QueryBuilder $query
     * @param string|string[] $status
     * 
     * @return QueryBuilder
     */
    public function scope_filter_status(QueryBuilder $query, $status) {
        $statuses = Arr::wrap($status);
        $statuses = !in_array('all', $statuses, true) && !in_array('any', $statuses, true) ? $statuses : [];

        if (!empty($statuses)) {
			return $query->where_in('post_status', $statuses);
		}

        return $query;
    }

    /**
     * Filter posts by post type
     * 
     * @param QueryBuilder $query
     * @param string|string[] $post_type
     * 
     * @return QueryBuilder
     */
    public function scope_filter_post_type(QueryBuilder $query, $post_type) {
        $post_types = Arr::wrap($post_type);

        if (empty($post_types)) {
            return $query;
        }

        return $query->where_in('post_type', $post_types);
    }

    /**
     * Exclude trashed posts
     * 
     * @param QueryBuilder $query
     * 
     * @return QueryBuilder
     */
    public function scope_exclude_trash(QueryBuilder $query) {
        return $query->where_not('post_status', PostStatus::TRASH);
    }
}