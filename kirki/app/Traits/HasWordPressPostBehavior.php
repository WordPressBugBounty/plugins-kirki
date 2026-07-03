<?php

namespace Kirki\App\Traits;

use Exception;
use Kirki\Framework\Constants\DateTimeFormats;


trait HasWordPressPostBehavior
{

    /**
     * Create post
     * 
     * @param array $data
     * 
     * @return static
     */
    public static function create_post(array $data)
    {
        if (!empty($data['post_date']) && $data['post_date'] instanceof \DateTimeInterface) {
            $date = $data['post_date'];
            $data['post_date'] = $date->format(DateTimeFormats::DB_DATETIME);

            $date_gmt = new \DateTime($date->format(DateTimeFormats::DB_DATETIME), $date->getTimezone());
            $date_gmt->setTimezone(new \DateTimeZone('UTC'));
            $data['post_date_gmt'] = $date_gmt->format(DateTimeFormats::DB_DATETIME);
        }

        $post_id = wp_insert_post($data, true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        if (!$post_id) {
            throw new Exception(__('Failed to create post.', 'kirki'));
        }

        return static::find($post_id);
    }

    /**
     * Update post
     * 
     * @param int $post_id
     * @param array $data
     * 
     * @return static
     */
    public static function update_post(int $post_id, array $data)
    {
        $data['ID'] = $post_id;
        
        if (!empty($data['post_date']) && $data['post_date'] instanceof \DateTimeInterface) {
            $date = $data['post_date'];
            $data['post_date'] = $date->format(DateTimeFormats::DB_DATETIME);

            $date_gmt = new \DateTime($date->format(DateTimeFormats::DB_DATETIME), $date->getTimezone());
            $date_gmt->setTimezone(new \DateTimeZone('UTC'));
            $data['post_date_gmt'] = $date_gmt->format(DateTimeFormats::DB_DATETIME);
        }

        $post_id = wp_update_post($data, true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        if (!$post_id) {
            throw new Exception(__('Failed to update post.', 'kirki'));
        }

        return static::find($post_id);
    }

    /**
     * Update or Create post
     * 
     * @param array $data
     * 
     * @return static
     */
    public static function update_or_create_post(array $data)
    {
        if (!empty($data['ID'])) {
            return static::update_post((int) $data['ID'], $data);
        }

        return static::create_post($data);
    }

    /**
     * Delete post
     * 
     * @param int $post_id
     * @param bool $force_delete
     * 
     * @return bool
     */
    public static function delete_post(int $post_id, bool $force_delete = false)
    {
        $is_deleted = wp_delete_post($post_id, $force_delete);

        return !empty($is_deleted);
    }
}