<?php

namespace Kirki\App\Wordpress;

use Kirki\App\Supports\Role;
use function Kirki\Framework\collection;

defined('ABSPATH') || exit;

use Kirki\App\Constants\AccessLevels;

class User extends \Kirki\Framework\Wordpress\User
{
    /**
     * Check if the current user has specific access
     *
     * @param string|string[] $access_level The access level to check access.
     * 
     * @return bool
     */
    public function has_access($access_level)
    {
        if (!$this->is_logged_in()) {
            return false;
        }

        $roles = $this->get_roles();

        $available_levels_by_roles = array_values(Role::get_access_levels_by_roles($roles));

        if (is_string($access_level)) {
            return in_array($access_level, $available_levels_by_roles, true);
        }

        return collection($access_level)->some(fn($level) => in_array($level, $available_levels_by_roles, true));
    }

    /**
     * Check if the user can edit content (full, editor or content access)
     *
     * @return bool
     */
    public function has_edit_access()
    {
        return $this->has_access([
            AccessLevels::FULL_ACCESS,
            AccessLevels::EDITOR_ACCESS,
            AccessLevels::CONTENT_ACCESS,
        ]);
    }

    /**
     * Check if the user has builder power access — page CRUD, symbols, fonts, etc.
     * Everything except site-admin features (settings, license, import/export).
     *
     * @return bool
     */
    public function has_builder_access()
    {
        return $this->has_access([
            AccessLevels::FULL_ACCESS,
            AccessLevels::EDITOR_ACCESS,
        ]);
    }

    /**
     * Check a post meta capability (edit_post, delete_post, read_post) on a single post.
     *
     * Users always pass on posts they authored, whatever the post type. Kirki's own post types
     * (template, popup, utility, symbol) are not registered with WordPress, so editing them
     * falls back to builder access and reading them stays open to any Kirki user.
     *
     * @param string $capability The post meta capability.
     * @param object $post       Post or Page model (needs ID, post_author, post_type).
     *
     * @return bool
     */
    public function can_access_post(string $capability, $post)
    {
        if (!$this->is_logged_in() || empty($post)) {
            return false;
        }

        if ((int) $post->post_author === (int) $this->get_id()) {
            return true;
        }

        if (!get_post_type_object($post->post_type)) {
            return $capability === 'read_post' || $this->has_builder_access();
        }

        return current_user_can($capability, (int) $post->ID);
    }

    /**
     * Check if the user is a WordPress administrator
     *
     * @return bool
     */
    public function is_administrator()
    {
        return $this->is_logged_in() && $this->has_role('administrator');
    }
}
