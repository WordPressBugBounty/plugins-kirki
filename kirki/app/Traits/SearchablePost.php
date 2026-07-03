<?php

namespace Kirki\App\Traits;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\QueryBuilder;

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
}