<?php

/**
 * Contract for validation rule.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Rule
{
    /**
     * Check the validity.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_valid();
    /**
     * Get the error message.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message();
    /**
     * Get the value.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_value();
    /**
     * Get the value for the rule.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function value();
    /**
     * Get the value for the rule.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function rule_value();
    /**
     * Check if the rule is for a specific data type.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_check_strict_data_type();
}
