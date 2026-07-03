<?php

/**
 * Class to handle the user meta Provides static methods for getting, setting, updating, and deleting user meta.
 *
 * @package    Framework
 * @subpackage Wordpress
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress;

\defined('ABSPATH') || exit;
use function Kirki\Framework\with_prefix;
use function Kirki\Framework\without_prefix;
class UserMeta
{
    /**
     * Retrieve user meta by ID and key.
     *
     * @param int $user_id The user id.
     * @param string $key The key.
     * @param bool $single The single.
     * @param bool $with_prefix The with prefix.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get(int $user_id, string $key = '', bool $single = \true, bool $with_prefix = \true)
    {
        if ($key === '') {
            $key = $with_prefix ? with_prefix($key) : $key;
            return get_user_meta($user_id, $key, $single);
        }
        $metadata = get_user_meta($user_id, '', $single);
        $updated_metadata = [];
        foreach ($metadata as $name => $value) {
            $name = $with_prefix ? without_prefix($name) : $name;
            $updated_metadata[$name] = $single ? $value[0] : $value;
        }
        return $updated_metadata;
    }
    /**
     * Get all user meta by ID.
     *
     * @param int $user_id The user id.
     * @param mixed $single The single.
     * @param bool $with_prefix The with prefix.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_all(int $user_id, $single = \true, bool $with_prefix = \true)
    {
        return static::get($user_id, '', $single, $with_prefix);
    }
    /**
     * Add the value of a specific user meta.
     *
     * @param int $user_id The user id.
     * @param string $key The key.
     * @param mixed $value The value.
     * @param mixed $unique The unique.
     * @param bool $with_prefix The with prefix.
     *
     * @return bool|int
     *
     * @since 1.0.0
     */
    public static function add(int $user_id, string $key, $value, $unique = \false, bool $with_prefix = \true)
    {
        $key = $with_prefix ? with_prefix($key) : $key;
        return add_user_meta($user_id, $key, $value, $unique);
    }
    /**
     * Add multiple user meta at once.
     *
     * @param int $user_id The user id.
     * @param array $meta_input The meta input.
     * @param bool $with_prefix The with prefix.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function add_many(int $user_id, array $meta_input, bool $with_prefix = \true)
    {
        foreach ($meta_input as $key => $value) {
            static::add($user_id, $key, $value, \false, $with_prefix);
        }
        return \true;
    }
    /**
     * Update the value of a specific user meta.
     *
     * @param int $user_id The user id.
     * @param string $key The key.
     * @param mixed $value The value.
     * @param mixed $prev_value The prev value.
     * @param bool $with_prefix The with prefix.
     *
     * @return bool|int
     *
     * @since 1.0.0
     */
    public static function update(int $user_id, string $key, $value, $prev_value = '', bool $with_prefix = \true)
    {
        $key = $with_prefix ? with_prefix($key) : $key;
        return update_user_meta($user_id, $key, $value, $prev_value);
    }
    /**
     * Update multiple user meta at once.
     *
     * @param int $user_id The user id.
     * @param array $meta_input The meta input.
     * @param bool $with_prefix The with prefix.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function update_many(int $user_id, array $meta_input, bool $with_prefix = \true)
    {
        foreach ($meta_input as $key => $value) {
            static::update($user_id, $key, $value, '', $with_prefix);
        }
        return \true;
    }
    /**
     * Delete a specific user meta.
     *
     * @param int $user_id The user id.
     * @param string $key The key.
     * @param mixed $value Metadata value. If provided,
     *                     rows will only be removed that match the value.
     *                     Must be serializable if non-scalar. Default empty.
     * @param bool $with_prefix The with prefix.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function delete(int $user_id, string $key, $value = '', bool $with_prefix = \true)
    {
        $key = $with_prefix ? with_prefix($key) : $key;
        return delete_user_meta($user_id, $key, $value);
    }
}
