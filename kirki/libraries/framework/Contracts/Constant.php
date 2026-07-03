<?php

/**
 * Interface requiring a static all method that returns every constant defined on the class.
 * Standardizes how constant holder classes expose their values to callers.
 * Complements HasConstants for classes that prefer an explicit contract.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Constant
{
    /**
     * Get all the constants
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function all();
}
