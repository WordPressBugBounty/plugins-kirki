<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\KirkiDateTimeFormat;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Constants\DateTimeFormats;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Supports\Facades\Date;

use function Kirki\App\get_editor_mode;
use function Kirki\App\get_timezone;
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
	 * @return array|false
	 */
	public function set_last_edited_datetime_of_stage_version(int $page_id)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);

		$total_versions = $staged_versions->count();

		if ($total_versions === 0) {
			return false;
		}

		$this->staged_versions = $staged_versions->map(function ($item, $index) use ($total_versions) {
			if ($index === $total_versions - 1) {
				$item['last_updated'] = Date::now(get_timezone(true))->format(DateTimeFormats::DB_DATETIME);
				$item['no_legacy_global_style'] = true;
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

			$staged_versions = $this->create_first_stage_version($page_id);
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
	private function create_first_stage_version(int $page_id)
	{
		$new_version = $this->add_stage_version($page_id, 1);

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

		return $this->publish_stage_version($page_id);
	}

	/**
	 * Add a new stage version
	 * 
	 * @param int $page_id
	 * @param int $version_number
	 * @param array $prev_versions
	 * @param array|false $being_restored
	 * @return int
	 */
	private function add_stage_version(int $page_id, int $version_number, array $prev_versions = [], $being_restored = false)
	{
		$version_name = $being_restored
			? sprintf(__('[Restored] %s', 'kirki'), $being_restored['name'])
			: wp_date(KirkiDateTimeFormat::HUMAN_READABLE_DAY_OF_MONTH_WITH_TIME); // @todo: improve later

		$datetime = wp_date(KirkiDateTimeFormat::DB_DATETIME); // @todo: improve later

		$new_version = [
			'version' => $version_number,
			'edited_by_id' => user()->get_id(),
			'edited_by' => user()->get_display_name(),
			'created_on' => $datetime,
			'last_updated' => $datetime,
			'name' => $version_name,
			'publish' => false,
			'no_legacy_global_style' => true,
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
	 * @return Collection
	 */
	public function publish_stage_version(int $page_id)
	{
		$stage_must = false;
		$version_id = $this->get_most_recent_stage_version($page_id, $stage_must);

		$this->staged_versions = $this->get_all_staged_versions($page_id)
			->map(function ($item) use ($version_id) {
				$is_published = isset($item['version']) && intval($item['version']) === intval($version_id);

				$item['publish'] = $is_published;
				$item['no_legacy_global_style'] = true;

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
		$reserved_names = $this->extract_style_block_name($global_style_blocks);

		$rename_map = [];

		foreach ($current_style_blocks as $block_index => $block) {
			if (!isset($block['name'])) {
				continue;
			}

			if (is_string($block['name'])) {
				$name = $this->normalize_style_block_name($block['name']);

				if (!isset($rename_map[$name])) {
					$rename_map[$name] = $this->generate_unique_style_block_name($name, $reserved_names);
				}

				$current_style_blocks[$block_index]['name'] = $rename_map[$name];

				continue;
			}

			if (!is_array($block['name'])) {
				continue;
			}

			foreach ($block['name'] as $name_index => $name) {
				if (!is_string($name)) {
					continue;
				}

				$name = $this->normalize_style_block_name($name);

				if (!isset($rename_map[$name])) {
					$rename_map[$name] = $this->generate_unique_style_block_name($name, $reserved_names);
				}

				$current_style_blocks[$block_index]['name'][$name_index] = $rename_map[$name];
			}
		}

		return $current_style_blocks;
	}

	/**
	 * Check or generate new class names
	 * 
	 * @todo: need to refactor
	 * 
	 * @param string $name
	 * @param array $reserved_names
	 * 
	 * @return string
	 */
	private function generate_unique_style_block_name(string $name, array &$reserved_names)
	{
		if (!isset($reserved_names[$name])) {
			$reserved_names[$name] = true;
			return $name;
		}

		$base_name = "{$name}-copy";
		$unique_name = $base_name;
		$counter = 2;

		while (isset($reserved_names[$unique_name])) {
			$unique_name = "{$base_name}-{$counter}";
			$counter++;
		}

		$reserved_names[$unique_name] = true;

		return $unique_name;
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
		$reserved = $this->extract_style_block_name($old_blocks);

		$rename_map = [];

		foreach ($new_blocks as &$block) {
			if (empty($block['name']) || !is_string($block['name'])) {
				continue;
			}

			$normalized = $this->normalize_style_block_name($block['name']);

			$unique = $this->generate_unique_style_block_name(
				$normalized,
				$reserved
			);

			if ($unique !== $normalized) {
				$rename_map[$block['name']] = $unique;
				$block['name'] = $unique;
			}
		}

		unset($block);

		if (empty($rename_map)) {
			return array_merge($old_blocks, $new_blocks);
		}

		
		foreach ($new_blocks as &$block) {
			if (!is_array($block['name'])) {
				continue;
			}

			foreach ($block['name'] as &$name) {
				if (!isset($rename_map[$name])) {
					continue;
				}

				$name = $rename_map[$name];
			}

			unset($name);
		}

		unset($block);

		return array_merge($old_blocks, $new_blocks);
	}

	private function extract_style_block_name(array $style_blocks)
	{
		$names = [];

		foreach ($style_blocks as $block) {
			if (!isset($block['name'])) {
				continue;
			}

			$block_names = Arr::wrap($block['name']);

			foreach ($block_names as $name) {
				if (!is_string($name)) {
					continue;
				}

				$names[$this->normalize_style_block_name($name)] = true;
			}
		}

		return $names;
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
}