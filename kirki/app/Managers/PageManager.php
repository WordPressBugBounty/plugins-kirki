<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\KirkiDateTimeFormat;
use Kirki\App\Constants\OptionKeys;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Supports\Facades\Option;
use Kirki\Framework\Constants\DateTimeFormats;
use Kirki\Framework\Supports\Facades\Date;

use function Kirki\App\get_editor_mode;
use function Kirki\App\get_timezone;
use function Kirki\App\is_falsy;
use function Kirki\App\is_truthy;
use function Kirki\Framework\collection;
use function Kirki\Framework\user;

class PageManager
{
	/** @var Collection */
	protected $staged_versions;

	/** @var array */
	protected $all_block_post_ids;


	public function __construct()
	{
		$this->staged_versions = collection();
	}

	/**
	 * Save random global style blocks
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_style_blocks(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::STYLE_BLOCKS, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCKS, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save global style blocks
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_deprecated_global_style_blocks(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save used style block ids
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_used_global_style_block_ids(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save random used style block ids
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_used_style_block_ids(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::USED_STYLE_BLOCK_IDS, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save used font list
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_used_font_list(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::USED_FONT_LIST, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::USED_FONT_LIST, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save kirki blocks
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_blocks(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::BLOCKS, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::BLOCKS, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	public function save_editor_mode(int $page_id)
	{
		PostMeta::update_meta_value(
			$page_id,
			PageMetaKeys::EDITOR_MODE,
			get_editor_mode()
		);
	}

	public function is_kirki_editor_mode(int $page_id)
	{
		$editor_mode = PostMeta::get_meta_value($page_id, PageMetaKeys::EDITOR_MODE);

		return $editor_mode === get_editor_mode();
	}

	/**
	 * Update last edited datetime of stage version
	 * 
	 * @param int $page_id
	 * @param bool $has_legacy_global_style default false
	 * @return array|false
	 */
	public function set_last_edited_datetime_of_stage_version(int $page_id, bool $has_legacy_global_style = false)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);

		$total_versions = $staged_versions->count();

		if ($total_versions === 0) {
			return false;
		}

		$this->staged_versions = $staged_versions->map(function ($item, $index) use ($total_versions, $has_legacy_global_style) {
			if ($index === $total_versions - 1) {
				$item['last_updated'] = Date::now(get_timezone(true))->format(DateTimeFormats::DB_DATETIME);
				$item['no_legacy_global_style'] = !$has_legacy_global_style;
			}

			return $item;
		});

		PostMeta::update_meta_value(
			$page_id,
			PageMetaKeys::STAGED_VERSIONS,
			$this->staged_versions->to_array()
		);

		return $this->staged_versions;
	}

	/**
	 * Get most recent stage version
	 * 
	 * @param int $page_id
	 * @param bool $stage_must
	 * @param bool $restoring
	 * @param int|bool $old_version
	 * 
	 * @return int
	 */
	public function get_most_recent_stage_version(int $page_id, $stage_must = true, $restoring = false, $old_version = false)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);
		$recent_stage_version = false;
		$version_number = 0;
		$being_restored = false;

		foreach ($staged_versions as $version) {
			if (is_array($version) && intval($version['version']) > $version_number) {
				$version_number = $version['version'];
				$recent_stage_version = $version;
			}

			if ($restoring && intval($old_version) === intval($version['version'])) {
				$being_restored = $version;
			}
		}

		if (
			$version_number === 0
			|| ($stage_must && isset($recent_stage_version['publish']) && $recent_stage_version['publish'])
			|| $restoring
		) {
			$version_number = $this->add_stage_version($page_id, $version_number + 1, $staged_versions->to_array(), $being_restored);
		}

		return (int) $version_number;
	}

	/**
	 * Get all staged versions
	 * 
	 * @param int $page_id
	 * @param bool $create_if_empty - default true
	 * @return Collection
	 */
	public function get_all_staged_versions(int $page_id, bool $create_if_empty = true)
	{
		if ($this->staged_versions->not_empty()) {
			return $this->staged_versions;
		}

		$staged_versions = PostMeta::get_meta_value($page_id, PageMetaKeys::STAGED_VERSIONS);

		if (empty($staged_versions) || !is_array($staged_versions)) {
			if (!$create_if_empty) {
				return $this->staged_versions = collection();
			}

			$staged_versions = $this->create_first_stage_version_if_empty($page_id);
		} else {
			$staged_versions = collection($staged_versions);
		}


		$this->staged_versions = $staged_versions->map(function ($item) {
			if (isset($item['edited_by']) && is_numeric($item['edited_by'])) {

				return array_merge(
					$item,
					[
						'edited_by_id' => (int) $item['edited_by'],
						'edited_by' => user((int) $item['edited_by'])->get_display_name(),
					]
				);
			}

			return $item;
		});

		return $this->staged_versions;
	}

	/**
	 * Create first stage version
	 * 
	 * @param int $page_id
	 * @return Collection
	 */
	private function create_first_stage_version_if_empty(int $page_id)
	{
		// When stage version is empty, it means there is legacy global style blocks
		$has_legacy_global_style = true;

		$new_version = $this->add_stage_version($page_id, 1, [], false, $has_legacy_global_style);

		$style_blocks_old_data = PostMeta::get_meta_value($page_id, PageMetaKeys::STYLE_BLOCKS, []);
		$this->save_style_blocks($page_id, $style_blocks_old_data, $new_version);

		$used_global_style_block_ids = PostMeta::get_meta_value($page_id, PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS, []);
		$this->save_used_global_style_block_ids($page_id, $used_global_style_block_ids, $new_version);

		$random_used_style_block_ids = PostMeta::get_meta_value($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS, []);
		$this->save_used_style_block_ids($page_id, $random_used_style_block_ids, $new_version);

		$used_font_list_data = PostMeta::get_meta_value($page_id, PageMetaKeys::USED_FONT_LIST, []);
		$this->save_used_font_list($page_id, $used_font_list_data, $new_version);

		$kirki_block_data = PostMeta::get_meta_value($page_id, PageMetaKeys::BLOCKS, []);
		$this->save_blocks($page_id, $kirki_block_data, $new_version);
		
		return $this->publish_stage_version($page_id, $has_legacy_global_style);
	}

	/**
	 * Add a new stage version
	 * 
	 * @param int $page_id
	 * @param int $version_number
	 * @param array $prev_versions
	 * @param array|false $being_restored
	 * @param bool $has_legacy_global_style default false
	 * @return int
	 */
	private function add_stage_version(int $page_id, int $version_number, array $prev_versions = [], $being_restored = false, $has_legacy_global_style = false)
	{
		$version_name = $being_restored
			? sprintf(__('[Restored] %s', 'kirki'), $being_restored['name'])
			: wp_date(KirkiDateTimeFormat::HUMAN_READABLE_DAY_OF_MONTH_WITH_TIME); // @todo: improve later

		$datetime = wp_date(KirkiDateTimeFormat::DB_DATETIME); // @todo: improve later

		if (!empty($being_restored)) {
			$has_legacy_global_style = !($being_restored['has_legacy_global_style'] ?? false);
		}

		$new_version = [
			'version' => $version_number,
			'edited_by_id' => user()->get_id(),
			'edited_by' => user()->get_display_name(),
			'created_on' => $datetime,
			'last_updated' => $datetime,
			'name' => $version_name,
			'publish' => false,
			'no_legacy_global_style' => !$has_legacy_global_style,
		];

		$prev_versions[] = $new_version;

		PostMeta::update_meta_value($page_id, PageMetaKeys::STAGED_VERSIONS, $prev_versions);

		$this->staged_versions = collection($prev_versions);

		return $version_number;
	}

	/**
	 * Publish stage version
	 * 
	 * @param int $page_id
	 * @param bool $has_legacy_global_style default false
	 * @return Collection
	 */
	public function publish_stage_version(int $page_id, bool $has_legacy_global_style = false)
	{
		$stage_must = false;
		$version_id = $this->get_most_recent_stage_version($page_id, $stage_must);

		$this->staged_versions = $this->get_all_staged_versions($page_id)
			->map(function ($item) use ($version_id, $has_legacy_global_style) {
				$is_published = isset($item['version']) && intval($item['version']) === intval($version_id);

				$item['publish'] = $is_published;
				$item['no_legacy_global_style'] = !$has_legacy_global_style;

				return $item;
			});

		PostMeta::update_meta_value(
			$page_id,
			PageMetaKeys::STAGED_VERSIONS,
			$this->staged_versions->to_array()
		);

		return $this->staged_versions;
	}

	/**
	 * Rename stage version
	 * 
	 * @param int $page_id
	 * @param int $version_id
	 * @param string $new_name
	 * @return Collection
	 */
	public function rename_stage_version(int $page_id, int $version_id, string $new_name)
	{
		$staged_versions = $this->get_all_staged_versions($page_id, false);
		
		$this->staged_versions = $staged_versions->map(function ($item) use ($version_id, $new_name) {
				if (is_array($item) && isset($item['version']) && intval($item['version']) === $version_id) {
					$item['name'] = $new_name;
				}

				return $item;
			}
		);

		PostMeta::update_meta_value($page_id, PageMetaKeys::STAGED_VERSIONS, $this->staged_versions->to_array());

		return $this->staged_versions;
	}

	/**
	 * Remove stage version
	 * 
	 * @param int $page_id
	 * @param int $version_id
	 * @return Collection
	 */
	public function remove_stage_version(int $page_id, int $version_id)
	{
		$stage_only = true;
		$meta_keys = [];

		foreach([
			PageMetaKeys::STYLE_BLOCKS,
			PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED,
			PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS,
			PageMetaKeys::USED_STYLE_BLOCK_IDS,
			PageMetaKeys::USED_FONT_LIST,
			PageMetaKeys::BLOCKS
		] as $key) {
			$meta_keys[] = $this->get_staged_meta_name($key, $page_id, $version_id, $stage_only);
		}

		PostMeta::query()
			->where('post_id', $page_id)
			->where_in('meta_key', $meta_keys)
			->delete();

		$staged_versions = $this->get_all_staged_versions($page_id, false);

		$this->staged_versions = $staged_versions->filter(function ($item) use ($version_id) {
			// Filter out the version which is matched and not published
			return !(
				is_array($item) 
				&& isset($item['publish'], $item['version']) 
				&& is_falsy($item['publish']) 
				&& intval($item['version']) === $version_id
			);
		})->values();
		
		PostMeta::update_meta_value($page_id, PageMetaKeys::STAGED_VERSIONS, $this->staged_versions->to_array());

		return $this->staged_versions;
	}

	protected function restore_page_meta(string $meta_key,int $page_id, int $old_version_id, int $new_version_id)
	{
		$old_meta_key = $this->get_staged_meta_name($meta_key, $page_id, $old_version_id);
		$old_data = PostMeta::get_meta_value($page_id, $old_meta_key);
		$new_meta_key = $this->get_staged_meta_name($meta_key, $page_id, $new_version_id);
		PostMeta::update_meta_value($page_id, $new_meta_key, $old_data);
	}

	/**
	 * Restore stage version
	 * 
	 * @param int $page_id
	 * @param int $old_version_id
	 * @return int return new version id
	 */
	public function restore_stage_version(int $page_id, int $old_version_id)
	{
		$new_version_id = $this->get_most_recent_stage_version($page_id, true, true, $old_version_id);

		$meta_keys = [
			PageMetaKeys::STYLE_BLOCKS,
			PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED,
			PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS,
			PageMetaKeys::USED_STYLE_BLOCK_IDS,
			PageMetaKeys::USED_FONT_LIST,
			PageMetaKeys::BLOCKS
		];

		foreach($meta_keys as $meta_key) {
			$this->restore_page_meta($meta_key, $page_id, $old_version_id, $new_version_id);
		}

		return $new_version_id;
	}

	/**
	 * Get the staged meta name
	 * 
	 * @param string $meta_name
	 * @param int $page_id
	 * @param int|false $stage_version
	 * @param bool $stage_only
	 * @return string
	 */
	public function get_staged_meta_name(string $meta_name, int $page_id, $stage_version = false, $stage_only = false)
	{
		if (!$stage_version) {
			$stage_must = false;
			$stage_version = $this->get_most_recent_stage_version($page_id, $stage_must);
		} elseif (!$stage_only) {
			$published_version = $this->get_published_stage_version($page_id);

			if ($published_version && $stage_version === $published_version) {
				return $meta_name;
			}
		}

		return sprintf('staged_%s_%s', $stage_version, $meta_name);
	}

	/**
	 * Get the published stage version info
	 * 
	 * @param int $page_id
	 * 
	 * @return array|null
	 */
	public function get_published_staged_version_info(int $page_id)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);

		// Find the first staged version that has 'publish' set to true
		foreach ($staged_versions as $item) {
			if (is_array($item) && isset($item['publish']) && is_truthy($item['publish'])) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Get the published stage version
	 * 
	 * @param int $page_id
	 * @return int|false
	 */
	public function get_published_stage_version(int $page_id)
	{
		$published_version = $this->get_published_staged_version_info($page_id);

		if ($published_version && isset($published_version['version'])) {
			return (int) $published_version['version'];
		}

		return false;
	}

	/**
	 * Get the stage version info
	 * 
	 * @param int $page_id
	 * @param int $stage_version
	 * 
	 * @return array|null
	 */
	public function get_staged_version_info(int $page_id, int $stage_version)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);

		// Find the first staged version that has 'publish' set to true
		foreach ($staged_versions as $item) {
			if (is_array($item) && isset($item['version']) && intval($item['version']) === intval($stage_version)) {
				return $item;
			}
		}

		return null;
	}

	private function has_legacy_global_style(int $page_id, int $stage_version)
	{
		$info = $this->get_staged_version_info($page_id, $stage_version);

		$has_no_legacy = isset($info['no_legacy_global_style']) && is_truthy($info['no_legacy_global_style']);

		return !$has_no_legacy;
	}

	/**
	 * This function will return page style blocks from option meta and post meta
	 * post meta for migration and option meta for global style block
	 *
	 * @param int $page_id post id.
	 * @param int|false $stage_version
	 * @return array
	 */
	protected function get_page_styleblocks_legacy(int $page_id, $stage_version = false)
	{
		$current_style_blocks = PostMeta::get_meta_value($page_id, PageMetaKeys::STYLE_BLOCKS, []);
		$legacy_global_style_blocks = GlobalData::get_deprecated_global_style_blocks();

		$current_style_blocks = $this->resolve_duplicate_current_style_block_names($current_style_blocks, $legacy_global_style_blocks);

		$merged_style_blocks = $current_style_blocks;

		if ($legacy_global_style_blocks) {
			$merged_style_blocks = array_merge($merged_style_blocks, $legacy_global_style_blocks);
		}

		$published_version = $this->get_published_stage_version($page_id);

		if (!$published_version || $stage_version === $published_version) {
			return $merged_style_blocks;
		}

		$staging_style_blocks = [];

		$meta_key = $this->get_staged_meta_name(PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED, $page_id, $stage_version);
		$global_stage_style_blocks = PostMeta::get_meta_value($page_id, $meta_key, []);

		if ($global_stage_style_blocks) {
			$staging_style_blocks = $global_stage_style_blocks;
		}

		$current_style_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCKS, $page_id, $stage_version);
		$current_stage_style_blocks = PostMeta::get_meta_value($page_id, $current_style_meta_key, []);

		if ($current_stage_style_blocks) {
			$staging_style_blocks = array_merge($staging_style_blocks, $current_stage_style_blocks);
		}

		if ($stage_version) {
			return $this->merge_style_blocks($merged_style_blocks, $staging_style_blocks);
		}

		return $this->merge_style_blocks($staging_style_blocks, $merged_style_blocks);
	}

	public function get_page_styleblocks(int $page_id, $stage_version = false)
	{
		$global_style_blocks = GlobalData::get_style_blocks();

		if ($this->has_legacy_global_style($page_id, $stage_version)) {
			$legacy_style_blocks = $this->get_page_styleblocks_legacy($page_id, $stage_version);

			$legacy_style_blocks = $this->resolve_duplicate_current_style_block_names($legacy_style_blocks, $global_style_blocks);

			return $this->merge_style_blocks($legacy_style_blocks, $global_style_blocks);
		}
		
		$published_version = $this->get_published_stage_version($page_id);

		// When published
		if (!$stage_version || $stage_version === $published_version) {
			$current_style_blocks = PostMeta::get_meta_value($page_id, PageMetaKeys::STYLE_BLOCKS, []);

			$current_style_blocks = $this->resolve_duplicate_current_style_block_names($current_style_blocks, $global_style_blocks);
		
			return $this->merge_style_blocks($current_style_blocks, $global_style_blocks);
		}

		$current_stage_style_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCKS, $page_id, $stage_version);
		$current_stage_style_blocks = PostMeta::get_meta_value($page_id, $current_stage_style_meta_key, []);

		$current_stage_style_blocks = $this->resolve_duplicate_current_style_block_names($current_stage_style_blocks, $global_style_blocks);

		return $this->merge_style_blocks($current_stage_style_blocks, $global_style_blocks);

	}

	/**
	 * Fix duplicate class name from random style blocks
	 * 
	 * @param array $current_style_blocks
	 * @param array $global_style_blocks
	 * 
	 * @return array
	 */
	private function resolve_duplicate_current_style_block_names($current_style_blocks, $global_style_blocks)
	{
		$global_class_names = $this->extract_class_names($global_style_blocks);
		$current_class_names = $this->extract_class_names($current_style_blocks);

		$duplicate_classes = array_intersect_key($current_class_names, $global_class_names);

		if (empty($duplicate_classes)) {
			return $current_style_blocks;
		}

		$class_map = $this->make_duplicate_classes_to_unique(
			$duplicate_classes,
			$global_class_names,
			$current_class_names
		);

		foreach ($current_style_blocks as &$style_block) {
			if (!isset($style_block['name'])) {
				continue;
			}

			if (is_string($style_block['name'])) {
				$class_name = $this->normalize_style_block_name($style_block['name']);

				if (isset($class_map[$class_name])) {
					$style_block['name'] = $class_map[$class_name];
				}

				continue;
			}

			if (!is_array($style_block['name'])) {
				continue;
			}

			foreach ($style_block['name'] as &$name) {
				$class_name = $this->normalize_style_block_name($name);

				if (isset($class_map[$class_name])) {
					$name = $class_map[$class_name];
				}
			}

			unset($name);
		}

		unset($style_block);

		return $current_style_blocks;
	}

	private function extract_class_names(array $style_blocks)
	{
		$class_names = [];

		foreach ($style_blocks as $style_block) {
			if (!isset($style_block['name']) || !is_string($style_block['name'])) {
				continue;
			}

			$class_names[$this->normalize_style_block_name($style_block['name'])] = true;
		}

		return $class_names;
	}


	/**
	 * Check or generate new class names
	 * 
	 * @param array $duplicate_classes
	 * @param array $global_class_names
	 * @param array $current_class_names
	 * 
	 * @return array
	 */
	private function make_duplicate_classes_to_unique(array $duplicate_classes, array $global_class_names, array $current_class_names)
	{
		foreach ($duplicate_classes as $class_name => $_) {
			$counter = 1;

			do {
				$new_class_name = $counter === 1
					? $class_name . '-copy'
					: $class_name . '-copy_' . $counter;

				$counter++;
			} while (
				isset($global_class_names[$new_class_name]) ||
				isset($current_class_names[$new_class_name])
			);

			$duplicate_classes[$class_name] = $new_class_name;
		}

		return $duplicate_classes;
	}

	/**
	 * Get class name from string
	 * 
	 * @param string $string
	 * 
	 * @return string
	 */
	private function normalize_style_block_name(string $string)
	{
		$class_name = strtolower(str_replace(' ', '-', trim($string)));

		return $class_name;
	}

	/**
	 * Merge style blocks
	 * 
	 * @param array $old_blocks
	 * @param array $new_blocks
	 * 
	 * @return array
	 */
	public function merge_style_blocks($old_blocks, $new_blocks)
	{
		$names_in_old_blocks = $this->extract_class_names($old_blocks);
    	$names_in_new_blocks = $this->extract_class_names($new_blocks);

		foreach ($new_blocks as $new_block_key => &$new_block) {
			if (empty($new_block['name']) || !is_string($new_block['name'])) {
				continue;
			}

			$original_name = $new_block['name'];
        	$normalized_name = strtolower($original_name);

			// If same ID exists in $old_blocks, remove it first (old behavior)
			if (isset($old_blocks[$new_block_key])) {
				unset($old_blocks[$new_block_key]);

				if (isset($names_in_old_blocks[$normalized_name])) {
					unset($names_in_old_blocks[$normalized_name]);
				}
			}

			if (!isset($names_in_old_blocks[$normalized_name])) {
				continue;
			}

			// If name already exists in $old_blocks, make it unique
			$suffix = 1;

			while (
				isset($names_in_old_blocks[$normalized_name . '_' . $suffix]) 
				|| isset($names_in_new_blocks[$normalized_name . '_' . $suffix])
			) {
				$suffix++;
			}

			$new_name = $original_name . '_' . $suffix;

			foreach ($new_blocks as &$block) {
				if (!isset($block['name']) || !is_array($block['name'])) {
					continue;
				}

				$block['name'] = array_map(fn($item) => $item === $original_name ? $new_name : $item, $block['name']);
			}

			unset($block);

			$new_block['name'] = $new_name;

			unset($names_in_new_blocks[$normalized_name]);
			$names_in_new_blocks[strtolower($new_name)] = true;
		}

		unset($new_block);

		// Use array_merge to keep old semantics
		return array_merge($old_blocks, $new_blocks);
	}

	/**
	 * Get variable mode
	 * 
	 * @param PageModel|PostModel|int $page
	 * 
	 * @return string
	 */
	public function get_variable_mode($page)
	{
		if ($page instanceof PageModel || $page instanceof PostModel) {
			$meta = isset($page->meta) && $page->meta->not_empty() ? $page->meta->pluck('meta_value', 'meta_key')->to_array() : [];

			if (isset($meta[PageMetaKeys::VARIABLE_MODE])) {
				return $meta[PageMetaKeys::VARIABLE_MODE] ?? 'inherit';
			}
		}

		return PostMeta::get_meta_value($page->ID, PageMetaKeys::VARIABLE_MODE, 'inherit');
	}

	/**
	 * Collect all block post IDs (including draft, published)
	 * 
	 * @return array
	 */
	public function get_all_block_post_ids()
	{
		if (!is_null($this->all_block_post_ids)) {
			return $this->all_block_post_ids;
		}

		return $this->all_block_post_ids = PostModel::query()
			->where_not_in('post_type', [PostTypes::SYMBOL, PostTypes::POPUP])
			->where_has('meta', fn($q) => $q->where('meta_key', PageMetaKeys::BLOCKS))
			->pluck('ID')
			->to_array();
	}

	/**
	 * Get most recent unpublished stage version
	 * 
	 * @param int $page_id
	 * 
	 * @return int|null
	 */
	public function get_most_recent_unpublished_stage_version(int $page_id)
	{
		$staged_versions = $this->get_all_staged_versions($page_id, false);

		$most_recent_unpublished_stage_version = $staged_versions->filter(function ($version) {
			return empty($version['publish']);
		})->last();

		if ($most_recent_unpublished_stage_version) {
			return (int) $most_recent_unpublished_stage_version['version'];
		}

		return null;
	}

	/**
	 * Get front page ID
	 * 
	 * @return int
	 */
	public function get_front_page_id()
	{
		return (int) Option::get(OptionKeys::PAGE_ON_FRONT, 0);
	}

	/**
	 * Is front page
	 * 
	 * @param int $page_id
	 * 
	 * @return bool
	 */
	public function is_front_page(int $page_id) {
		return $this->get_front_page_id() === $page_id;
	}
}