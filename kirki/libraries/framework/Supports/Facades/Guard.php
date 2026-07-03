<?php

/**
 * Facade proxy for PolicyManager authorization checks.
 * Exposes authorize, allows, and denies for ability testing against models.
 * Resolves from the policy container binding.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
/**
 * Facade proxy for PolicyManager authorization checks.
 *
 * @method static mixed authorize(string $ability, $model = null, ...$arguments)
 * @method static bool allows(string $ability, $model = null, ...$arguments)
 * @method static bool denies(string $ability, $model = null, ...$arguments)
 * @see    \Framework\Managers\PolicyManager
 */
class Guard extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'policy';
    }
}
