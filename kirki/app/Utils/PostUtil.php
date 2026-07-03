<?php

namespace Kirki\App\Utils;

defined('ABSPATH') || exit;

use Kirki\App\Models\Page;
use Kirki\App\Models\Post;
use Kirki\Framework\Sanitizer;
use ReflectionClass;
use WP_Post;

class PostUtil
{
    /**
     * Converts a Post object to a WP_Post object
     * 
     * @param Post|Page $post
     */
    public static function get_wp_post($post)
    {
        $vars = [];
        $reflection = new ReflectionClass(WP_Post::class);
        
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $vars[$name] = isset($post->{$name}) ? $post->{$name} : null;
        }

        $vars['filter'] = 'raw';

        return new WP_Post((object) sanitize_post($vars, 'raw'));
    }

    /**
     * Returns the permalink for a Post object
     * 
     * @param Post|Page $post
     * @param bool $leave_name
     * @return string|false
     */
    public static function get_permalink($post, $leave_name = false)
    {
        return get_permalink(static::get_wp_post($post), $leave_name);
    }

    /**
     * Get post id from url
     * 
     * @return int
     */
    public static function get_post_id_from_url()
    {
        if (isset($GLOBALS['wp']->query_vars[KIRKI_CONTENT_MANAGER_PREFIX . '_child_post'])) {
			return $GLOBALS['wp']->query_vars[KIRKI_CONTENT_MANAGER_PREFIX . '_child_post'];
		} elseif (isset($GLOBALS['wp']->query_vars[KIRKI_CONTENT_MANAGER_PREFIX . '_parent_post']) && false) { // Disable content manager archive page logic
			return $GLOBALS['wp']->query_vars[KIRKI_CONTENT_MANAGER_PREFIX . '_parent_post'];
		}

        $post_id = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);

        if ($post_id) {
            return Sanitizer::apply_rule($post_id, Sanitizer::INT);
        }

        $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

        if ($post_id) {
            return Sanitizer::apply_rule($post_id, Sanitizer::INT);
        }

        $post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);

        if ($post_id) {
            return Sanitizer::apply_rule($post_id, Sanitizer::INT);
        }

        return (int) get_the_ID();
    }
}