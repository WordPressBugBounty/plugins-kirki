<?php

namespace Kirki\App\Services;

use DateTimeZone;
use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\App\Constants\PostStatus;
use Kirki\App\DTO\CollectionItemListFilterDTO;
use Kirki\App\DTO\UpsertCollectionItemDTO;
use Kirki\App\Models\CollectionItem;
use Kirki\App\Models\PostMeta;
use Kirki\App\Models\Reference;
use Kirki\App\Supports\ContentManager;
use Kirki\App\Supports\DateTime;
use Kirki\Framework\Constants\DateTimeFormats;
use Kirki\Framework\Exceptions\NotFoundException;
use function Kirki\Framework\collection;

/**
 * Business logic for content manager collection items
 */
class CollectionItemService
{
    /**
     * Paginate the items of a collection, applying search and filters.
     *
     * @param CollectionItemListFilterDTO $filters
     * @return \Kirki\Framework\Database\Query\Paginator
     */
    public function paginated(CollectionItemListFilterDTO $filters)
    {
        $query = CollectionItem::with(['references', 'fields'])
            ->where('post_parent', $filters->post_parent);

        if (!empty($filters->query)) {
            $query->search($filters->query);
        }

        if (!empty($filters->exclude_post_ids)) {
            $query->where_not_in('ID', $filters->exclude_post_ids);
        }

        if (!empty($filters->post_status) && $filters->post_status !== 'all') {
            $query->where('post_status', $filters->post_status);
        }

        if (!empty($filters->post_date)) {
            $date = DateTime::relative_date($filters->post_date);

            if ($date) {
                $query->where_date('post_date', '>=', $date);
            }
        }

        if (!empty($filters->post_modified)) {
            $date = DateTime::relative_date($filters->post_modified);

            if ($date) {
                $query->where_date('post_modified', '>=', $date);
            }
        }

        return $query->order_by('ID', 'desc')->paginate($filters->limit, $filters->page);
    }

    /**
     * Get a single item, or null when missing.
     *
     * @param int $id
     * @return CollectionItem|null
     */
    public function get_single(int $id)
    {
        return CollectionItem::with(['references', 'fields'])->find($id);
    }

    /**
     * Create or update a collection item and its field values.
     *
     * @param UpsertCollectionItemDTO $data
     * @return CollectionItem
     */
    public function create_or_update(UpsertCollectionItemDTO $data)
    {
        $existing = $data->ID ? CollectionItem::find($data->ID) : null;

        $data_array = $data->to_array();
        $data_array['post_type'] = ContentManager::get_child_post_post_type_value((int) $data->post_parent);

        if ($data->post_status === PostStatus::FUTURE && $data->post_date) {
            if ($data->post_date instanceof \DateTimeInterface) {
                $date = $data->post_date;
            } else {
                $date = new \DateTime($data->post_date, wp_timezone());
            }
            $data_array['post_date'] = $date->format(DateTimeFormats::DB_DATETIME);

            $date_gmt = new \DateTime($date->format(DateTimeFormats::DB_DATETIME), $date->getTimezone());
            $date_gmt->setTimezone(new DateTimeZone('UTC'));
            $data_array['post_date_gmt'] = $date_gmt->format(DateTimeFormats::DB_DATETIME);
        } elseif ($existing && $existing->post_status !== PostStatus::PUBLISH && $data->post_status === PostStatus::PUBLISH) {
            $data_array['post_date'] = current_time('mysql');
            $data_array['post_date_gmt'] = current_time('mysql', true);
        } else {
            unset($data_array['post_date']);
        }

        $post = CollectionItem::update_or_create_post($data_array);

        $this->save_field_values((int) $post->ID, (int) $data->post_parent, $data->fields ?? []);

        return $this->get_single($post->ID);
    }

    /**
     * Delete an item and its children.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        $record = $this->get_single($id);

        if (!$record) {
            return false;
        }

        $record->meta()->delete();

        return $record->delete();
    }

    /**
     * Duplicate an item and its reference rows.
     *
     * @param int $id
     * @return CollectionItem|null
     */
    public function duplicate(int $id)
    {
        $record = $this->get_single($id);

        if (!$record) {
            return null;
        }

        $new_post = CollectionItem::create_post([
            'post_title' => $record->post_title . ' Copy',
            'post_status' => PostStatus::DRAFT,
            'post_type' => ContentManager::get_child_post_post_type_value($record->post_parent),
            'post_parent' => $record->post_parent,
        ]);

        if (!$new_post) {
            return null;
        }

        $this->duplicate_references($id, $new_post->ID);
        $this->duplicate_normal_fields($record, $new_post);

        return $this->get_single($new_post->ID);
    }

    /**
     * Bulk delete items (supports the '*' wildcard for all children).
     *
     * @param array $post_ids
     * @param int   $parent
     * @return bool
     */
    public function bulk_delete(array $post_ids, int $parent)
    {
        if (in_array('*', $post_ids, true)) {
            $post_ids = CollectionItem::where('post_parent', $parent)->get()->pluck('ID')->all();
        }

        if (empty($post_ids)) {
            return false;
        }

        CollectionItem::where_in('ID', $post_ids)->where('post_parent', $parent)->delete();
        return PostMeta::where_in('post_id', $post_ids)->delete();
    }

    /**
     * Bulk duplicate items (supports the '*' wildcard for all children).
     *
     * @param array $post_ids
     * @param int   $parent
     * @return CollectionItem[]
     */
    public function bulk_duplicate(array $post_ids, int $parent)
    {
        $items = [];

        if (in_array('*', $post_ids, true)) {
            $post_ids = CollectionItem::where('post_parent', $parent)->get()->pluck('ID')->all();
        } else {
            $post_ids = CollectionItem::where_in('ID', $post_ids)->where('post_parent', $parent)->get()->pluck('ID')->all();
        }

        if (empty($post_ids)) {
            throw new NotFoundException(__('No items found', 'kirki'));
        }

        foreach ($post_ids as $id) {
            $duplicated = $this->duplicate($id);

            if ($duplicated) {
                $items[] = $duplicated;
            }
        }

        return $items;
    }

    /**
     * Persist an item's field values and reference rows.
     *
     * @param int   $post_id
     * @param int   $collection_id
     * @param array $fields
     * @return void
     */
    protected function save_field_values(int $post_id, int $collection_id, array $fields)
    {
        if (empty($fields)) {
            return;
        }

        $available_fields = ContentManager::get_available_custom_fields($collection_id);
        $available_field_meta_keys = collection($available_fields)->map(function ($item) use ($collection_id) {
            return ContentManager::get_child_post_meta_key_using_field_id($collection_id, $item['id']);
        })->all();

        $normal_field_rows = PostMeta::where('post_id', $post_id)->where_in('meta_key', $available_field_meta_keys)->get();
        $normal_field_map = [];

        foreach ($normal_field_rows as $normal_field_row) {
            $normal_field_map[$normal_field_row->meta_key] = $normal_field_row;
        }

        $normal_field_values = [];
        $ref_field_values = [];

        foreach ($available_fields as $index => $field) {
            if (!isset($fields[$field['id']])) {
                continue;
            }

            $meta_key = $available_field_meta_keys[$index];
            $type = $field['type'] ?? '';

            if ($type === CustomFieldTypes::REFERENCE || $type === CustomFieldTypes::MULTI_REFERENCE) {
                $new_values = $fields[$field['id']] ?? [];

                if (!is_array($new_values) || empty($new_values)) {
                    continue;
                }

                foreach ($new_values as $new_value) {
                    if (!isset($new_value['value'])) {
                        continue;
                    }

                    $ref_field_values[] = [
                        'post_id' => $post_id,
                        'field_meta_key' => $meta_key,
                        'ref_post_id' => $new_value['value'], // @todo: we should check if the id belongs to the target post type
                    ];
                }

            } else {
                $normal_field_values[] = [
                    'meta_id' => $normal_field_map[$meta_key]->meta_id ?? null,
                    'post_id' => $post_id,
                    'meta_key' => $meta_key,
                    'meta_value' => maybe_serialize($fields[$field['id']]),
                ];
            }
        }

        PostMeta::upsert($normal_field_values, ['meta_value']);

        Reference::where('post_id', $post_id)->where_in('field_meta_key', $available_field_meta_keys)->delete();
        Reference::upsert($ref_field_values, ['ref_post_id']);
    }

    /**
     * Duplicate reference rows from one post to another.
     *
     * @param int $old_id
     * @param int $new_id
     * @return void
     */
    protected function duplicate_references(int $old_id, int $new_id)
    {
        $references = Reference::where('post_id', $old_id)->get()->map(function ($reference) use ($new_id) {
            return [
                'post_id' => $new_id,
                'field_meta_key' => $reference->field_meta_key,
                'ref_post_id' => $reference->ref_post_id,
            ];
        })->all();

        Reference::insert($references);
    }

    /**
     * Duplicate the normal fields from one item to another.
     *
     * @param CollectionItem $collection_item
     * @param CollectionItem $new_item
     * @return void
     */
    protected function duplicate_normal_fields(CollectionItem $collection_item, CollectionItem $new_item)
    {
        $meta = $collection_item->meta->map(function ($meta_item) use ($new_item) {
            return [
                'post_id' => $new_item->ID,
                'meta_key' => $meta_item->meta_key,
                'meta_value' => maybe_serialize($meta_item->meta_value),
            ];
        })->all();

        PostMeta::insert($meta);
    }
}
