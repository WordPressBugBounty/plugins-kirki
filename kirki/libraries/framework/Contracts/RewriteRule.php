<?php

/**
 * Defines the shape of a WordPress rewrite rule registration.
 * Requires pattern, query string, rule stack, and query var accessors.
 * Used by classes that contribute custom URL routing rules.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface RewriteRule
{
    /**
     * Get the pattern to match the rewrite rule.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_pattern() : string;
    /**
     * Get the query string to add to the rewrite rule.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_query_string() : string;
    /**
     * Get the rule stack to add the rule to.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_rule_stack() : string;
    /**
     * Get the query vars to add to the rewrite rule.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_query_vars() : array;
    /**
     * Get the template path to load.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_template_path() : string;
    /**
     * Handle the rewrite rule to manage adding the rule
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function handle();
}
