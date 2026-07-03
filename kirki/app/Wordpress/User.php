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

    public function has_edit_access()
    {
        return $this->has_access([
            AccessLevels::FULL_ACCESS,
            AccessLevels::CONTENT_ACCESS,
        ]);
    }
}