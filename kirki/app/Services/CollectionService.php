<?php

namespace Kirki\App\Services;

use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;
use Kirki\App\DTO\ListFilterDTO;
use Kirki\App\DTO\Collection\UpsertCollectionDTO;
use Kirki\App\Models\Collection;
use Kirki\App\Models\PostMeta;
use Kirki\App\Models\Reference;
use Kirki\App\Supports\ContentManager;
use function Kirki\Framework\with_prefix;

/**
 * Business logic for content manager collections (parent posts).
 *
 * Mirrors the legacy ContentManagerHelper parent-post behaviour while keeping
 * data access in the model layer.
 */
class CollectionService
{
    /**
     * Paginate collections.
     *
     * @param ListFilterDTO $filters
     * @return \Kirki\Framework\Database\Query\Paginator
     */
    public function paginated(ListFilterDTO $filters)
    {
        return Collection::with(['fields', 'basic_fields'])
            ->with_count('items')
            ->paginate($filters->limit, $filters->page);
    }

    /**
     * Build the single-collection payload (mirrors legacy format_single_post).
     *
     * @param int  $id
     * @return Collection|null
     */
    public function get_single(int $id)
    {
        $collection_item = Collection::with(['fields', 'basic_fields'])
            ->with_count('items')
            ->find($id);

        if (!$collection_item) {
            return null;
        }

        return $collection_item;
    }

    /**
     * Create or update a collection and its field definitions.
     *
     * @param UpsertCollectionDTO $dto
     * @return Collection|null
     */
    public function create_or_update(UpsertCollectionDTO $dto)
    {
        $data = array_merge($dto->to_array(), [
            'post_type' => PostTypes::CONTENT_MANAGER,
            'post_status' => PostStatus::PUBLISH,
        ]);

        $collection = Collection::update_or_create_post($data);

        $this->save_fields($collection->ID, $data['fields'] ?? [], $data['basic_fields'] ?? []);

        return $this->get_single($collection->ID);
    }

    /**
     * Persist field definitions and clean up removed fields.
     *
     * @param int   $post_id
     * @param array $fields
     * @param array $basic_fields
     * @return void
     */
    protected function save_fields(int $post_id, array $fields, array $basic_fields)
    {
        $this->cleanup_old_field_data($post_id, array_column($fields, 'id'));

        foreach ($fields as $key => $field) {
            if (($field['type'] ?? '') === CustomFieldTypes::REFERENCE || ($field['type'] ?? '') === CustomFieldTypes::MULTI_REFERENCE) {
                $fields[$key]['editRefCollection'] ??= false;
            }
        }

        PostMeta::update_or_create([
            'post_id' => $post_id,
            'meta_key' => with_prefix('cm_fields'),
        ], [
            'meta_value' => $fields
        ]);

        if (!empty($basic_fields)) {
            PostMeta::update_or_create([
                'post_id' => $post_id,
                'meta_key' => with_prefix('cm_basic_fields'),
            ], [
                'meta_value' => $basic_fields
            ]);
        }
    }

    /**
     * Delete a collection and all of its children.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        $collection = $this->get_single($id);

        if (empty($collection)) {
            return false;
        }

        // @todo improve this deletion system later
        $collection_item_service = new CollectionItemService();

        $collection_item_service->bulk_delete(['*'], $collection->ID);
        $collection->meta()->delete();

        return $collection->delete();
    }

    /**
     * Duplicate a collection
     *
     * @param int $id
     * @return Collection|null
     */
    public function duplicate(int $id)
    {
        $collection = $this->get_single($id);

        if (!$collection) {
            return null;
        }

        $new_collection = Collection::create_post([
            'post_title' => $collection->post_title . ' Copy',
            'post_content' => $collection->post_content,
            'post_type' => $collection->post_type,
            'post_parent' => $collection->post_parent,
            'post_status' => $collection->post_status,
        ]);

        $meta_data = $collection->meta->map(function ($item) use ($new_collection) {
            return [
                'post_id' => $new_collection->ID,
                'meta_key' => $item->meta_key,
                'meta_value' => maybe_serialize($item->meta_value)
            ];
        })->all();

        $new_collection->meta()->insert($meta_data);

        return $this->get_single($new_collection->ID);
    }

    /**
     * Delete field meta data.
     * 
     * @param int $post_id
     * @param array $new_ids
     * @return void
     */
    protected function cleanup_old_field_data(int $post_id, array $new_ids)
    {
        $prev_fields = PostMeta::where('post_id', $post_id)
            ->where('meta_key', with_prefix('cm_fields'))
            ->first()
            ->meta_value ?? [];

        if (empty($prev_fields)) {
            return;
        }

        $deleted_ids = array_diff(array_column($prev_fields, 'id'), $new_ids);

        $deleted_meta_keys = [];

        foreach ($deleted_ids as $field_id) {
            $deleted_meta_keys[] = ContentManager::get_child_post_meta_key_using_field_id($post_id, $field_id);
        }

        if (!empty($deleted_meta_keys)) {
            PostMeta::where_in('meta_key', $deleted_meta_keys)->delete();
            Reference::where_in('field_meta_key', $deleted_meta_keys)->delete();
        }
    }
}
