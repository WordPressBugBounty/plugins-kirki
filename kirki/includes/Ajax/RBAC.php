<?php
/**
 * Role manager api
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
use Kirki\App\Supports\Role;
use Kirki\HelperFunctions;


/**
 * Role manager API Class
 */
class RBAC
{

	/**
	 * Get members based on role
	 *
	 * @return void wp_send_json.
	 */
	public static function members_based_on_role()
	{
		$roles = static::get_all_roles();
		$user_count = static::get_user_count_based_on_role($roles);
		$user_dps = static::get_user_dp($roles);
		$access_levels = static::get_access_level($roles);

		wp_send_json(
			array(
				'user_count' => $user_count,
				'roles' => $roles,
				'dp' => $user_dps,
				'access' => $access_levels,
			)
		);
	}

	/**
	 * Update access lavel
	 *
	 * @return void wp_send_json.
	 */
	public static function update_access_level()
	{
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$role_name = HelperFunctions::sanitize_text(isset($_POST['roleName']) ? $_POST['roleName'] : null);
		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$access = HelperFunctions::sanitize_text(isset($_POST['access']) ? $_POST['access'] : null);

		// The access level of `administrator` & `subscriber` role can't be updated.
		if (in_array($role_name, array('administrator', 'subscriber'), true)) {
			wp_send_json(false);
			die();
		}
		;

		if ($role_name && $access) {
			$res = update_option('kirki_' . $role_name, $access, false);
			if ($res) {
				wp_send_json(true);
			}
		}
		wp_send_json(false);
	}

	/**
	 * Get editor access lavel
	 *
	 * @return void wp_send_json.
	 */
	public static function get_editor_access_level()
	{
		if (HelperFunctions::is_api_call_from_editor_preview() && HelperFunctions::is_api_header_post_editor_preview_token_valid()) {
			wp_send_json(
				array(
					'access'  => KIRKI_ACCESS_LEVELS['FULL_ACCESS'],
					'isAdmin' => false,
				)
			);
		}

		$levels = array(
			KIRKI_ACCESS_LEVELS['FULL_ACCESS'],
			KIRKI_ACCESS_LEVELS['EDITOR_ACCESS'],
			KIRKI_ACCESS_LEVELS['CONTENT_ACCESS'],
			KIRKI_ACCESS_LEVELS['VIEW_ACCESS'],
		);

		$access = KIRKI_ACCESS_LEVELS['NO_ACCESS'];

		if ($curr_user = wp_get_current_user()) {
			foreach ($levels as $level) {
				if (HelperFunctions::has_access($level)) {
					$access = $level;
					break;
				}
			}
		} else {
			$access = KIRKI_ACCESS_LEVELS['VIEW_ACCESS'];
		}

		wp_send_json(
			array(
				'access'  => $access,
				'isAdmin' => HelperFunctions::user_is_admin(),
			)
		);
	}

	/**
	 * Get user count based on role
	 *
	 * @return int count.
	 */
	private static function get_user_count_based_on_role()
	{
		$result = count_users();
		$user_count = $result['avail_roles'];
		unset($user_count['none']); // This is not needed.
		return $user_count;
	}

	/**
	 * Get user DP
	 *
	 * @param array $roles wp roles.
	 * @return array
	 */
	private static function get_user_dp($roles = array())
	{
		if (count($roles)) {
			$dp = array();

			foreach ($roles as $role) {
				$role_id = $role['id'];

				$users = get_users(
					array(
						'role' => $role_id,
						'number' => 3,
					)
				);
				$user_ids = array();

				foreach ($users as $user) {
					$user_ids[] = $user->ID;
				}

				$avatars = array();
				foreach ($user_ids as $user_id) {
					$avatar = get_avatar_url($user_id);
					$avatars[] = $avatar;
				}

				$dp[$role_id] = $avatars;
			}

			return $dp;
		}
		return array();
	}

	/**
	 * Get all roles
	 *
	 * @return array
	 */
	public static function get_all_roles()
	{
		global $wp_roles;
		$role_list = $wp_roles->roles;
		$roles = array();

		if (is_array($role_list)) {
			foreach ($role_list as $key => $val) {
				$roles[] = array(
					'id' => $key,
					'name' => $val['name'],
				);
			}
		}

		return $roles;
	}

	/**
	 * Get access level
	 *
	 * @param array $roles wp roles.
	 * @return array
	 * 
	 * @deprecated
	 * @see \Kirki\App\Supports\Role::get_access_levels_by_roles()
	 */
	public static function get_access_level($roles)
	{
		$role_ids = array();

		foreach ($roles as $role) {
			$role_ids[] = $role['id'];
		}

		return Role::get_access_levels_by_roles($role_ids);
	}
}
