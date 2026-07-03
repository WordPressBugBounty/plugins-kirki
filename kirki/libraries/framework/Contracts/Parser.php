<?php

/**
 * Contract for content parsers that transform a string input into processed output.
 * Defines a single parse method accepting raw content.
 * Used where pluggable parsing strategies are needed.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Parser
{
    /**
     * Parse the content.
     *
     * @param string $content The content.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function parse(string $content) : string;
}
