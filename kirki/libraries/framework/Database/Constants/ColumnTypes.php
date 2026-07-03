<?php

/**
 * Registry of supported database column type constants for schema definitions.
 * Centralizes canonical type names used by the structure builder and SQL compiler.
 * Keeps column declarations consistent across migrations and model metadata.
 *
 * @package    Framework
 * @subpackage Database\Constants
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Constants;

\defined('ABSPATH') || exit;
class ColumnTypes
{
    /**
     * Big integer column type.
     *
     * @since 1.0.0
     */
    public const BIGINT = 'big_integer';
    /**
     * String column type.
     *
     * @since 1.0.0
     */
    public const STRING = 'string';
    /**
     * Text column type.
     *
     * @since 1.0.0
     */
    public const TEXT = 'text';
    /**
     * Medium text column type.
     *
     * @since 1.0.0
     */
    public const MEDIUM_TEXT = 'medium_text';
    /**
     * Long text column type.
     *
     * @since 1.0.0
     */
    public const LONG_TEXT = 'long_text';
    /**
     * Integer column type.
     *
     * @since 1.0.0
     */
    public const INTEGER = 'integer';
    /**
     * Float column type.
     *
     * @since 1.0.0
     */
    public const FLOAT = 'float';
    /**
     * Double column type.
     *
     * @since 1.0.0
     */
    public const DOUBLE = 'double';
    /**
     * Decimal column type.
     *
     * @since 1.0.0
     */
    public const DECIMAL = 'decimal';
    /**
     * Boolean column type.
     *
     * @since 1.0.0
     */
    public const BOOLEAN = 'boolean';
    /**
     * Datetime column type.
     *
     * @since 1.0.0
     */
    public const DATETIME = 'datetime';
    /**
     * Date column type.
     *
     * @since 1.0.0
     */
    public const DATE = 'date';
    /**
     * Time column type.
     *
     * @since 1.0.0
     */
    public const TIME = 'time';
    /**
     * Timestamp column type.
     *
     * @since 1.0.0
     */
    public const TIMESTAMP = 'timestamp';
    /**
     * Tiny integer column type.
     *
     * @since 1.0.0
     */
    public const TINYINT = 'tinyint';
    /**
     * Enum column type.
     *
     * @since 1.0.0
     */
    public const ENUM = 'enum';
    /**
     * Set column type.
     *
     * @since 1.0.0
     */
    public const SET = 'set';
    /**
     * IP address column type.
     *
     * @since 1.0.0
     */
    public const IP_ADDRESS = 'ip_address';
    /**
     * JSON column type.
     *
     * @since 1.0.0
     */
    public const JSON = 'json';
    /**
     * UUID column type.
     *
     * @since 1.0.0
     */
    public const UUID = 'uuid';
    /**
     * Year column type.
     *
     * @since 1.0.0
     */
    public const YEAR = 'year';
}
