<?php

namespace Kirki\App\Resources;

defined('ABSPATH') || exit;

use Kirki\App\Supports\PageUrl;
use Kirki\Framework\Resource;

class PageSettingsResource extends Resource
{
    /**
     * Convert the collection resource to an array.
     *
     * @return array The collection data as an associative array.
     */
    public function to_array()
    {
        $page_url = PageUrl::create($this->resource);

        return [
            'page_title'      => $this->post_title,
            'slug'            => $this->post_name,
            'page_description' => $this->post_excerpt,
            'post_status'     => $this->post_status,
            'post_url'        => str_replace(get_http_origin(), '', $page_url->get_page_permalink()),
            // NB: These are sent debugging purpose, please don't remove them without being sure.
            'page_full_url'   => $page_url->get_page_permalink(),
            'home_url'        => $page_url->get_home_url(),
            'origin'          => get_http_origin(),
            'site_url'        => $page_url->get_site_url(),
        ];
    }
}
