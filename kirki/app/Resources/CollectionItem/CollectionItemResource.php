<?php

namespace Kirki\App\Resources\CollectionItem;

use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\App\Supports\ContentManager;
use Kirki\Framework\Resource;

/**
 * Presentation for a content manager collection item (child post).
 *
 * Field values are formatted here from eager-loaded relations: reference
 * values are grouped from the `references` relation by `field_meta_key`, and
 * non-reference values are read from the item's post meta.
 */
class CollectionItemResource extends Resource
{
    /**
     * Convert the collection item resource to an array.
     *
     * @return array The collection item data as an associative array.
     */
    public function to_array()
    {
        return [
            'ID' => (int) $this->ID,
            'post_author' => (int) $this->post_author,
            'post_date' => $this->post_date,
            'post_content' => $this->post_content,
            'post_title' => $this->post_title,
            'post_excerpt' => $this->post_excerpt,
            'post_status' => $this->post_status,
            'post_name' => $this->post_name,
            'pinged' => $this->pinged,
            'post_modified' => $this->post_modified,
            'post_parent' => (int) $this->post_parent,
            'url' => home_url('?p=' . $this->ID),
            'menu_order' => (int) $this->menu_order,
            'post_type' => $this->post_type,
            'comment_count' => (int) $this->comment_count,

            'fields' => $this->format_fields() ?? [],
        ];
    }

    /**
     * Build the item's field values keyed by field id.
     *
     * Reference / multi-reference fields are resolved from the grouped
     * `references` relation; all other fields are read from the eager-loaded
     * `fields` (post meta) relation, falling back to the field's default value.
     *
     * @return array
     */
    protected function format_fields()
    {
        $grouped_references = $this->grouped_references();
        $non_reference_fields = $this->non_reference_fields();
        $fields = [];

        foreach ($this->custom_fields() as $parent_field) {
            $meta_key = ContentManager::get_child_post_meta_key_using_field_id($this->post_parent, $parent_field['id']);
            $type = $parent_field['type'] ?? '';

            if ($type === CustomFieldTypes::REFERENCE || $type === CustomFieldTypes::MULTI_REFERENCE) {
                $fields[$parent_field['id']] = $grouped_references[$meta_key] ?? [];
            } elseif (array_key_exists($meta_key, $non_reference_fields)) {
                $fields[$parent_field['id']] = $non_reference_fields[$meta_key];
            } else {
                $fields[$parent_field['id']] = $parent_field['default_value'] ?? '';
            }
        }

        return $fields;
    }

    /**
     * Map the item's eager-loaded field meta as meta_key => meta_value.
     *
     * @return array<string, mixed>
     */
    protected function non_reference_fields()
    {
        $map = [];

        foreach ($this->fields ?? [] as $meta) {
            $map[$meta->meta_key] = $meta->meta_value;
        }

        return $map;
    }

    /**
     * Group the item's reference rows by `field_meta_key`.
     *
     * @return array<string, array<int, array{value: int, title: string}>>
     */
    protected function grouped_references()
    {
        $grouped = [];

        foreach ($this->references ?? [] as $reference) {
            $grouped[$reference->field_meta_key][] = [
                'value' => (int) $reference->ref_post_id,
                'title' => get_the_title($reference->ref_post_id),
            ];
        }

        return $grouped;
    }

    /**
     * The collection's custom fields definitions.
     *
     * @return array
     */
    protected function custom_fields()
    {
        static $cache = [];

        if (!isset($cache[$this->post_parent])) {
            $item_with_relation = $this->load_missing('collection.fields');
            $collection = $item_with_relation->collection ?? null;
            $fields = !is_null($collection->fields) && !empty($collection->fields->meta_value) ? $collection->fields->meta_value : [];

            $cache[$this->post_parent] = $fields;
        }

        return is_array($cache[$this->post_parent]) ? $cache[$this->post_parent] : [];
    }
}
