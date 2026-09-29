<?php
/**
 * Role manager api
 *
 * @package kirki
 */

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\AccessLevels;
use function Kirki\Framework\collection;

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
	 * Access levels are hard-coded per role via AccessLevels::ROLE_ACCESS_MAP.
	 * Roles not present in the map get NO_ACCESS.
	 *
	 * @param array<string> $roles
	 *
	 * @return array<string, string>
	 */
	public static function get_access_levels_by_roles(array $roles)
	{
		$all_accesses = [];

		foreach ($roles as $role) {
			$all_accesses[$role] = AccessLevels::ROLE_ACCESS_MAP[$role] ?? AccessLevels::NO_ACCESS;
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
