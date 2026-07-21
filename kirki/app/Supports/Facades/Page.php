<?php

namespace Kirki\App\Supports\Facades;

defined( 'ABSPATH' ) || exit;

use Kirki\App\Managers\PageManager;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Facade;
/**
 * Page Support Facade
 * @method static void save_style_blocks(int $page_id, $data = [], $staging_version = false)
 * @method static void save_deprecated_global_style_blocks(int $page_id, $data = [], $staging_version = false)
 * @method static void save_used_global_style_block_ids(int $page_id, $data = [], $staging_version = false)
 * @method static void save_used_style_block_ids(int $page_id, $data = [], $staging_version = false)
 * @method static void save_used_font_list(int $page_id, $data = [], $staging_version = false)
 * @method static void save_blocks(int $page_id, $data = [], $staging_version = false)
 * @method static void save_editor_mode(int $page_id)
 * @method static bool is_kirki_editor_mode(int $page_id)
 * @method static array|false set_last_edited_datetime_of_stage_version(int $page_id)
 * @method static int get_most_recent_stage_version(int $page_id, $stage_must = true, $restoring = false, $old_version = false)
 * @method static Collection get_all_staged_versions(int $page_id, bool $create_if_empty = true) 
 * @method static Collection publish_stage_version(int $page_id) 
 * @method static string get_staged_meta_name(string $meta_name, int $page_id, $stage_version = false, $stage_only = false)
 * @method static array|null get_published_staged_version_info(int $page_id) 
 * @method static int|false get_published_stage_version(int $page_id)
 * @method static array get_page_styleblocks(int $page_id, $stage_version = false) 
 * @method static mixed get_global_data_using_key(string $key)
 * @method static array merge_style_blocks($a, $b)
 * @method static string get_variable_mode($page)
 * @method static array get_all_block_post_ids()
 * @method static int|null get_most_recent_unpublished_stage_version(int $page_id)
 * @method static array|null get_staged_version_info(int $page_id, int $stage_version)
 * 
 * @see \Kirki\App\Managers\PageManager
 */
class Page extends Facade
{
    public static function get_accessor()
    {
        return PageManager::class;
    }
}
