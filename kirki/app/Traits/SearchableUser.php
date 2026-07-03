<?php

namespace Kirki\App\Traits;

defined('ABSPATH') || exit;

use Kirki\App\Constants\UserMetaKeys;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Supports\Arr;

trait SearchableUser {
    /**
     * Search users
     * 
     * @param QueryBuilder $query
     * @param string $keyword
     * 
     * @return QueryBuilder
     */
    public function scope_search(QueryBuilder $query, $keyword) {
        if ($keyword === '') {
            return $query;
        }

        if (str_contains($keyword, '@')) {
            return $query->where_like('user_email', $keyword . '%');
		}
        
        if (is_numeric($keyword)) {
            return $query->where(function (QueryBuilder $query) use ($keyword) {
                $query->where_like('user_login', $keyword . '%')
                    ->or_where_like('ID', $keyword . '%');
            });
		} 
        
        if (preg_match('|^https?://|', $keyword) && !(is_multisite() && wp_is_large_network('users'))) {
            return $query->where_like('user_url', $keyword . '%');
		}
        
        return $query->where(function (QueryBuilder $query) use ($keyword) {
                $query->where_like('user_login', $keyword . '%')
                    ->or_where_like('user_url', $keyword . '%')
                    ->or_where_like('user_email', $keyword . '%')
                    ->or_where_like('user_nicename', $keyword . '%')
                    ->or_where_like('display_name', $keyword . '%');
        });
    }

    /**
     * Search users by role
     * 
     * @param QueryBuilder $query
     * @param string|array $role
     * 
     * @return QueryBuilder
     */
    public function scope_role(QueryBuilder $query, $role) {
        if (empty($role)) {
            return $query;
        }

        $roles = Arr::wrap($role);  

        return $query->where_has('meta', function (QueryBuilder $query) use ($roles) {
            $query->where('meta_key', '=', UserMetaKeys::CAPABILITIES);

            foreach ($roles as $role) {
                $query->where_like('meta_value', '%' . $role . '%');
            }
        });
    }
}