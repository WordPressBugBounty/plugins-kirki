<?php
/**
 * Role manager api
 *
 * @package kirki
 */

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\AccessLevels;
use Kirki\Framework\Supports\Facades\Option;
use function Kirki\Framework\collection;
use function Kirki\Framework\with_prefix;

class Role
{
	/**
	 * Get all roles
	 *
	 * @return array
	 */
	public static function get_all()
	{
		global $wp_roles;

		return is_array($wp_roles->roles) ? array_keys($wp_roles->roles) : [];
	}

	/**
	 * Get all the access levels by roles
	 * 
	 * @param array<string> $roles
	 * 
	 * @return array<string, string>
	 */
	public static function get_access_levels_by_roles(array $roles)
	{
		$all_accesses = [];
		$option_keys = collection($roles)->map(fn($role) => with_prefix($role))->to_array();

		$options = Option::get($option_keys, [], false);

		foreach ($roles as $role) {
			$prefixed_role = with_prefix($role);

			if (in_array($role, AccessLevels::USERS_DEFAULT_FULL_ACCESS, true)) {
				$all_accesses[$role] = AccessLevels::FULL_ACCESS;
			}

			if (!empty($options[$prefixed_role])) {
				$all_accesses[$role] = $options[$prefixed_role];
			}
		}

		return $all_accesses;
	}

	public static function get_roles_by_levels($levels)
	{
		$all_roles = static::get_all();
		$roles_with_levels = static::get_access_levels_by_roles($all_roles);

		return array_keys(collection($roles_with_levels)
			->filter(fn($level, $role) => in_array($level, $levels, true))
			->all());
	}
}
