<?php

namespace Kirki\App\Supports;

use Kirki\App\Models\Post;
use Kirki\App\Models\PostMeta;
use function Kirki\Framework\with_prefix;

/**
 * Pure, reusable helpers for the Content Manager.
 *
 * This class holds only side-effect-free helpers (constants, key/string
 * builders and the slug check). All fetching/checking/saving business logic
 * lives in the service layer, and reference-table access goes through the
 * {@see \Kirki\App\Models\Reference} model.
 */
class ContentManager
{
    public const RESERVED_SLUG_WORDS = ['attachment', 'author', 'category', 'comment', 'feed', 'page', 'post', 'tag', 'profile', 'index'];

    /**
     * Get available custom fields for a collection.
     *
     * @param int $collection_id
     * @return array
     */
    public static function get_available_custom_fields(int $collection_id)
    {
        $fields = PostMeta::where('post_id', $collection_id)
            ->where('meta_key', with_prefix('cm_fields'))
            ->first();

        if (empty($fields)) {
            return [];
        }

        return $fields->meta_value ? $fields->meta_value : [];
    }

    /**
     * Validate a post slug against reserved words and WordPress rules.
     *
     * @param int    $post_id
     * @param string $post_type
     * @param string $post_name
     * @return bool
     */
    public static function validate_slug(int $post_id, string $post_type, string $post_name)
    {
        if (in_array($post_name, static::RESERVED_SLUG_WORDS, true)) {
            return false;
        }

        $post_query = Post::where('post_type', $post_type)
            ->where('post_name', $post_name);

        if (!empty($post_id)) {
            $post_query->where('ID', '!=', $post_id);
        }

        $post = $post_query->first();

        return empty($post);
    }

    /**
     * Build the child post type value for a parent collection.
     *
     * @param int $post_parent
     * @return string
     */
    public static function get_child_post_post_type_value($post_parent)
    {
        return with_prefix('cm_' . $post_parent);
    }

    /**
     * Build the child post meta key for a parent collection field.
     *
     * @param int    $post_parent
     * @param string $field_id
     * @return string
     */
    public static function get_child_post_meta_key_using_field_id($post_parent, $field_id)
    {
        return with_prefix('cm_field_' . $post_parent . '_' . $field_id);
    }
}
