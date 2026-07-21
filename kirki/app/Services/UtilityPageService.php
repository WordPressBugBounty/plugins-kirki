<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\Ajax\Collection;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Constants\UtilityPageType;
use Kirki\App\Models\Page as PageModel;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Http\Response;

class UtilityPageService
{
    /**
     * Create a new instance of the service
     * @return UtilityPageService
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Check if a utility page exists
     * 
     * @param string $utility_type
     * 
     * @return bool
     */
    public function exists(string $utility_type)
    {
        if (!in_array($utility_type, UtilityPageType::get_constant_values(), true)) {
            throw new Exception(
                /* translators: %s: Utility page type */
                sprintf(esc_html__('Invalid utility page type: %s', 'kirki'), esc_html($utility_type)), 
                Response::UNPROCESSABLE_ENTITY
            );
        }

        return PageModel::query()
            ->where('post_type', '=', PostTypes::UTILITY)
            ->where_has('meta', function (QueryBuilder $query) use ($utility_type) {
                $query->where('meta_key', '=', PageMetaKeys::UTILITY_PAGE_TYPE)
                    ->where('meta_value', '=', $utility_type);
            })
            ->exists();
    }

    /**
     * Get all utility pages
     * 
     * @return Collection
     */
    public function get_utility_pages()
    {
        return PageModel::query()
            ->with([
                'meta' => function (QueryBuilder $query) {
                    $query->where('meta_key', '=', PageMetaKeys::UTILITY_PAGE_TYPE)
                        ->where_in('meta_value', UtilityPageType::get_constant_values());
                }
            ])
            ->where('post_type', '=', PostTypes::UTILITY)
            ->get();
    }
}