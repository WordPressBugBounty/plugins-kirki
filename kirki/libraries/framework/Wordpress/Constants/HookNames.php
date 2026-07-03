<?php

/**
 * Named constants for WordPress action and filter hook strings used by the framework.
 * Covers init, admin, REST, mail, and template hooks among others.
 * Prevents typos and enables IDE autocompletion for hook registration.
 *
 * @package    Framework
 * @subpackage Wordpress\Constants
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Constants;

\defined('ABSPATH') || exit;
class HookNames
{
    public const WP = 'wp';
    public const PLUGINS_LOADED = 'plugins_loaded';
    public const INIT = 'init';
    public const ADMIN_INIT = 'admin_init';
    public const ADMIN_NOTICES = 'admin_notices';
    public const REST_API_INIT = 'rest_api_init';
    public const ADMIN_MENU = 'admin_menu';
    public const ADMIN_ENQUEUE_SCRIPT = 'admin_enqueue_scripts';
    public const WP_ENQUEUE_SCRIPT = 'wp_enqueue_scripts';
    public const REGISTER_ROLE = 'register_role';
    public const REGISTER_TAXONOMY = 'register_taxonomy';
    public const REGISTER_POST_TYPE = 'register_post_type';
    public const AJAX_QUERY_ATTACHMENTS_ARGS = 'ajax_query_attachments_args';
    public const ADMIN_COMMENT_TYPES_DROPDOWN = 'admin_comment_types_dropdown';
    public const WP_MAIL_FROM = 'wp_mail_from';
    public const WP_MAIL_FROM_NAME = 'wp_mail_from_name';
    public const WP_PHP_MAILER_INIT = 'phpmailer_init';
    public const EDITABLE_ROLES = 'editable_roles';
    public const WP_TRASH_POST = 'wp_trash_post';
    public const LOGIN_REDIRECT = 'login_redirect';
    public const TEMPLATE_INCLUDE = 'template_include';
    public const GET_BLOCK_TEMPLATES = 'get_block_templates';
}
