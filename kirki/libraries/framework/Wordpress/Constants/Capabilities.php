<?php

/**
 * Catalog of standard WordPress capability slug constants.
 * Uses HasConstants for runtime enumeration of all defined caps.
 * Reference for authorization checks and capability registration hooks.
 *
 * @package    Framework
 * @subpackage Wordpress\Constants
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Constants;

\defined('ABSPATH') || exit;
use Kirki\Framework\Concerns\HasConstants;
class Capabilities
{
    use HasConstants;
    public const SWITCH_THEMES = 'switch_themes';
    public const EDIT_THEMES = 'edit_themes';
    public const ACTIVATE_PLUGINS = 'activate_plugins';
    public const EDIT_PLUGINS = 'edit_plugins';
    public const EDIT_USERS = 'edit_users';
    public const EDIT_FILES = 'edit_files';
    public const MANAGE_OPTIONS = 'manage_options';
    public const MODERATE_COMMENTS = 'moderate_comments';
    public const MANAGE_CATEGORIES = 'manage_categories';
    public const MANAGE_LINKS = 'manage_links';
    public const UPLOAD_FILES = 'upload_files';
    public const IMPORT = 'import';
    public const UNFILTERED_HTML = 'unfiltered_html';
    public const EDIT_POSTS = 'edit_posts';
    public const EDIT_OTHERS_POSTS = 'edit_others_posts';
    public const EDIT_PUBLISHED_POSTS = 'edit_published_posts';
    public const PUBLISH_POSTS = 'publish_posts';
    public const EDIT_PAGES = 'edit_pages';
    public const READ = 'read';
    public const LEVEL_10 = 'level_10';
    public const LEVEL_9 = 'level_9';
    public const LEVEL_8 = 'level_8';
    public const LEVEL_7 = 'level_7';
    public const LEVEL_6 = 'level_6';
    public const LEVEL_5 = 'level_5';
    public const LEVEL_4 = 'level_4';
    public const LEVEL_3 = 'level_3';
    public const LEVEL_2 = 'level_2';
    public const LEVEL_1 = 'level_1';
    public const LEVEL_0 = 'level_0';
    public const EDIT_OTHERS_PAGES = 'edit_others_pages';
    public const EDIT_PUBLISHED_PAGES = 'edit_published_pages';
    public const PUBLISH_PAGES = 'publish_pages';
    public const DELETE_PAGES = 'delete_pages';
    public const DELETE_OTHERS_PAGES = 'delete_others_pages';
    public const DELETE_PUBLISHED_PAGES = 'delete_published_pages';
    public const DELETE_POSTS = 'delete_posts';
    public const DELETE_OTHERS_POSTS = 'delete_others_posts';
    public const DELETE_PUBLISHED_POSTS = 'delete_published_posts';
    public const DELETE_PRIVATE_POSTS = 'delete_private_posts';
    public const EDIT_PRIVATE_POSTS = 'edit_private_posts';
    public const READ_PRIVATE_POSTS = 'read_private_posts';
    public const DELETE_PRIVATE_PAGES = 'delete_private_pages';
    public const EDIT_PRIVATE_PAGES = 'edit_private_pages';
    public const READ_PRIVATE_PAGES = 'read_private_pages';
    public const DELETE_USERS = 'delete_users';
    public const CREATE_USERS = 'create_users';
    public const UNFILTERED_UPLOAD = 'unfiltered_upload';
    public const EDIT_DASHBOARD = 'edit_dashboard';
    public const UPDATE_PLUGINS = 'update_plugins';
    public const DELETE_PLUGINS = 'delete_plugins';
    public const INSTALL_PLUGINS = 'install_plugins';
    public const UPDATE_THEMES = 'update_themes';
    public const INSTALL_THEMES = 'install_themes';
    public const UPDATE_CORE = 'update_core';
    public const LIST_USERS = 'list_users';
    public const REMOVE_USERS = 'remove_users';
    public const PROMOTE_USERS = 'promote_users';
    public const EDIT_THEME_OPTIONS = 'edit_theme_options';
    public const DELETE_THEMES = 'delete_themes';
    public const EXPORT = 'export';
}
