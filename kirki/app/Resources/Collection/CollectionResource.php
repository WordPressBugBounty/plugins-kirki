<?php

namespace Kirki\App\Resources\Collection;

use Kirki\Framework\Resource;

class CollectionResource extends Resource
{
    /**
     * Convert the collection resource to an array.
     *
     * @return array The collection data as an associative array.
     */
    public function to_array()
    {
        $basic_fields = $this->basic_fields ? ($this->basic_fields->meta_value ?? null) : null;

        if (empty($basic_fields)) {
            $basic_fields = [
                'post_title' => ['title' => 'Name', 'help_text' => ''],
                'post_name' => ['title' => 'Slug', 'help_text' => ''],
            ];
        }

        return [
            'ID' => (int) $this->ID,
            'post_author' => (int) $this->post_author,
            'post_date' => $this->post_date,
            'post_content' => $this->post_content,
            'post_title' => $this->post_title,
            'post_excerpt' => $this->post_excerpt,
            'post_status' => $this->post_status,
            'post_name' => $this->post_name,
            'post_modified' => $this->post_modified,
            'post_parent' => (int) $this->post_parent,
            'url' => home_url('?p=' . $this->ID),
            'menu_order' => (int) $this->menu_order,
            'post_type' => $this->post_type,
            'comment_count' => (int) $this->comment_count,

            'fields' => !empty($this->fields->meta_value) ? $this->fields->meta_value : [],
            'item_count' => $this->items_count ?? 0,
            'basic_fields' => $basic_fields,
        ];
    }
}
