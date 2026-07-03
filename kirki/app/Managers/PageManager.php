<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\KirkiDateTimeFormat;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\DTO\Page\EditorPagePayloadDTO;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Collections\Collection;

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
	public function save_random_global_style_blocks(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::STYLE_BLOCK_RANDOM, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCK_RANDOM, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save global style blocks
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_global_style_blocks(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::STYLE_BLOCK, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCK, $page_id, $staging_version);

		PostMeta::update_meta_value($page_id, $new_meta_key, $data);
	}

	/**
	 * Save used style block ids
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
	 * Save random used style block ids
	 * 
	 * @param int $page_id
	 * @param array $data
	 * @param int|false $staging_version
	 */
	public function save_random_used_style_block_ids(int $page_id, $data = [], $staging_version = false)
	{
		if (!$staging_version) {
			PostMeta::update_meta_value($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS_RANDOM, $data);
			return;
		}

		$new_meta_key = $this->get_staged_meta_name(PageMetaKeys::USED_STYLE_BLOCK_IDS_RANDOM, $page_id, $staging_version);

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

	/**
	 * save staging data
	 * 
	 * @param EditorPagePayloadDTO $payload
	 * @return array
	 */
	public function save_staging_data(EditorPagePayloadDTO $payload)
	{
		$staging_version = $this->get_most_recent_stage_version($payload->page->ID);
		$this->set_last_edited_datetime_of_stage_version($payload->page->ID);

		$page_data = $payload->data;

		if (isset($page_data['styles'])) {
			$new_random_styles = $page_data['styles'] ?? [];
			$new_global_styles = []; // global Styleblocks which won't be saved in publish but stage
			$add_to_publish_global = []; // global Styleblocks which won't be saved in stage but publish

			foreach ($new_random_styles as $key => $style) {

				if (
					(isset($style['isDefault']) && $style['isDefault'] === true)
					|| (isset($style['isGlobal']) && $style['isGlobal'] === true)
				) {

					if (isset($style['fromStage']) && $style['fromStage']) {
						$new_global_styles[$key] = $style;
					} else {
						// Adding fromStage true because publish version saves only styleblocks having fromStage
						$style['fromStage'] = true;
						$add_to_publish_global[$key] = $style;
					}

					unset($new_random_styles[$key]);
				}
			}

			$this->save_random_global_style_blocks($payload->page->ID, $new_random_styles, $staging_version);
			$this->save_global_style_blocks($payload->page->ID, $new_global_styles, $staging_version);

			if (count($add_to_publish_global) > 0) {
				$page_data['styles'] = $add_to_publish_global;
			} else {
				unset($page_data['styles']); // Unset if no global style blocks for publish version
			}
		}

		if (isset($page_data['usedStyles'])) {
			$this->save_used_style_block_ids($payload->page->ID, $page_data['usedStyles'], $staging_version);
			unset($page_data['usedStyles']);
		}

		if (isset($page_data['usedStyleIdsRandom'])) {
			$this->save_random_used_style_block_ids($payload->page->ID, $page_data['usedStyleIdsRandom'], $staging_version);
			unset($page_data['usedStyleIdsRandom']);
		}

		if (isset($page_data['usedFonts'])) {
			$this->save_used_font_list($payload->page->ID, $page_data['usedFonts'], $staging_version);
			unset($page_data['usedFonts']);
		}

		if (isset($page_data['blocks'])) {
			$this->save_blocks($payload->page->ID, ['blocks' => $page_data['blocks']], $staging_version);
			unset($page_data['blocks']);
		}

		return [
			'staging_version' => $staging_version,
			'data' => $page_data
		];
	}

	/**
	 * Update last edited datetime of stage version
	 * 
	 * @param int $page_id
	 * @return array|false
	 */
	private function set_last_edited_datetime_of_stage_version(int $page_id)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);

		if ($staged_versions->count() === 0) {
			return false;
		}

		$datetime = wp_date(KirkiDateTimeFormat::DB_DATETIME); // @todo: improve later

		$this->staged_versions = $staged_versions->map(function ($item, $index) use ($datetime, $staged_versions) {
			if ($index === $staged_versions->count() - 1) {
				return array_merge($item, ['last_updated' => $datetime]);
			}

			return $item;
		});

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

		$random_global_style_blocks_old_data = PostMeta::get_meta_value(
			$page_id,
			PageMetaKeys::STYLE_BLOCK_RANDOM,
			[]
		);
		$this->save_random_global_style_blocks($page_id, $random_global_style_blocks_old_data, $new_version);

		$this->save_global_style_blocks($page_id, [], $new_version);

		$used_style_block_ids_data = PostMeta::get_meta_value($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS, []);
		$this->save_used_style_block_ids($page_id, $used_style_block_ids_data, $new_version);

		$random_used_style_block_ids_data = PostMeta::get_meta_value($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS_RANDOM, []);
		$this->save_random_used_style_block_ids($page_id, $random_used_style_block_ids_data, $new_version);

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
	 * This function will update page style blocks into option meta and post meta
	 * post meta for migration and option meta for global style block
	 *
	 * @param int $page_id post id.
	 * @param array $style_blocks styleblocks.
	 */
	public function update_page_styleblocks(int $page_id, $style_blocks)
	{
		$prev_style_blocks = $this->get_page_styleblocks($page_id);
		$style_blocks = array_merge($prev_style_blocks, $style_blocks);
		$global_style_blocks = [];

		foreach ($style_blocks as $key => $style_block) {
			if (
				(isset($style_block['isDefault']) && $style_block['isDefault'] === true)
				|| (isset($style_block['isGlobal']) && $style_block['isGlobal'] === true)
			) {
				$global_style_blocks[$style_block['id']] = $style_block;
				unset($style_blocks[$key]);
			}
		}

		GlobalData::update_global_style_blocks($global_style_blocks);
		$this->save_random_global_style_blocks($page_id, $style_blocks);
	}

	/**
	 * This function will return page style blocks from option meta and post meta
	 * post meta for migration and option meta for global style block
	 *
	 * @param int $page_id post id.
	 * @param int|false $stage_version
	 * @return array
	 */
	public function get_page_styleblocks(int $page_id, $stage_version = false)
	{
		$random_style_blocks = PostMeta::get_meta_value($page_id, PageMetaKeys::STYLE_BLOCK_RANDOM, []);
		$global_style_blocks = GlobalData::get_global_style_blocks();

		$random_style_blocks = $this->fix_duplicate_class_name_from_random_sbs($random_style_blocks, $global_style_blocks);

		$merged_style_blocks = [];

		if ($random_style_blocks) {
			$merged_style_blocks = array_merge($merged_style_blocks, $random_style_blocks);
		}

		if ($global_style_blocks) {
			$merged_style_blocks = array_merge($merged_style_blocks, $global_style_blocks);
		}

		$published_version = $this->get_published_stage_version($page_id);

		if (!$published_version || $stage_version === $published_version) {
			return $merged_style_blocks;
		}

		$staging_style_blocks = [];

		$meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCK, $page_id, $stage_version);
		$stage_style = PostMeta::get_meta_value($page_id, $meta_key, []);

		if ($stage_style) {
			$staging_style_blocks = $stage_style;
		}

		$random_meta_key = $this->get_staged_meta_name(PageMetaKeys::STYLE_BLOCK_RANDOM, $page_id, $stage_version);
		$stage_style = PostMeta::get_meta_value($page_id, $random_meta_key, []);

		if ($stage_style) {
			$staging_style_blocks = array_merge($staging_style_blocks, $stage_style);
		}

		if ($stage_version) {
			return $this->merge_style_blocks($merged_style_blocks, $staging_style_blocks);
		}

		return $this->merge_style_blocks($staging_style_blocks, $merged_style_blocks);
	}

	/**
	 * Fix duplicate class name from random sbs
	 * 
	 * @param array $random_style_blocks
	 * @param array $global_style_blocks
	 * 
	 * @return array
	 */
	private function fix_duplicate_class_name_from_random_sbs($random_style_blocks, $global_style_blocks)
	{
		$global_class_names = [];
		$random_class_names = [];

		if ($global_style_blocks) {
			foreach ($global_style_blocks as $key => $value) {
				if (isset($value['name']) && is_string($value['name'])) {
					$global_class_names[$this->get_class_name_from_string($value['name'])] = true;
				}
			}
		}

		if ($random_style_blocks) {
			foreach ($random_style_blocks as $key => $value) {
				if (isset($value['name']) && is_string($value['name'])) {
					$random_class_names[$this->get_class_name_from_string($value['name'])] = true;
				}
			}
		}

		$class_match = [];

		foreach ($random_class_names as $key => $value) {
			if (isset($global_class_names[$key])) {
				$class_match[$key] = true;
			}
		}

		$class_match = $this->check_or_generate_new_class_names($class_match, $global_class_names, $random_class_names);

		if (count($class_match) > 0) {
			foreach ($random_style_blocks as $random_style_block_key => $random_style_block) {
				if (!isset($random_style_block['name'])) {
					continue;
				}

				if (is_string($random_style_block['name'])) {
					$class_name = $this->get_class_name_from_string($random_style_block['name']);

					if (isset($class_match[$class_name])) {
						$random_style_blocks[$random_style_block_key]['name'] = $class_match[$class_name];
					}

					continue;
				}

				if (is_array($random_style_block['name'])) {
					foreach ($random_style_block['name'] as $style_block_key => $style_block) {
						$class_name = $this->get_class_name_from_string($style_block);

						if (isset($class_match[$class_name])) {
							$random_style_blocks[$random_style_block_key]['name'][$style_block_key] = $class_match[$class_name];
						}
					}
				}
			}
		}

		return $random_style_blocks;
	}

	/**
	 * Check or generate new class names
	 * 
	 * @todo: need to refactor
	 * 
	 * @param array $class_match
	 * @param array $global_class_names
	 * @param array $random_class_names
	 * 
	 * @return array
	 */
	private function check_or_generate_new_class_names($class_match, $global_class_names, $random_class_names)
	{
		foreach ($class_match as $key => $value) {
			$temp_class = $key;
			$found = true;

			while ($found) {
				if (isset($global_class_names[$temp_class]) || isset($random_class_names[$temp_class])) {
					$temp_class = $temp_class . '-copy';
				} else {
					$found = false;
				}
			}

			$class_match[$key] = $temp_class;
		}

		return $class_match;
	}

	/**
	 * Get class name from string
	 * 
	 * @param string $string
	 * 
	 * @return string
	 */
	public function get_class_name_from_string(string $string)
	{
		$class_name = strtolower(str_replace(' ', '-', $string));

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
		$names_in_old_block = [];

		foreach ($old_blocks as $old_block) {
			if (!empty($old_block['name']) && is_string($old_block['name'])) {
				$names_in_old_block[strtolower($old_block['name'])] = true;
			}
		}

		$names_in_new_block = [];

		foreach ($new_blocks as $new_block) {
			if (!empty($new_block['name']) && is_string($new_block['name'])) {
				$names_in_new_block[strtolower($new_block['name'])] = true;
			}
		}

		foreach ($new_blocks as $new_block_index => &$new_block_value) {
			if (empty($new_block_value['name']) || !is_string($new_block_value['name'])) {
				continue;
			}

			$new_block_name = strtolower($new_block_value['name']);

			// If same ID exists in A, remove it first (old behavior)
			if (isset($old_blocks[$new_block_index])) {
				unset($old_blocks[$new_block_index]);
				if (isset($names_in_old_block[$new_block_name])) {
					unset($names_in_old_block[$new_block_name]);
				}
			}

			// If name already exists in A, make it unique
			if (isset($names_in_old_block[$new_block_name])) {
				$i = 1;

				while (isset($names_in_old_block[$new_block_name . '_' . $i]) || isset($names_in_new_block[$new_block_name . '_' . $i])) {
					$i++;
				}

				$new_name = $new_block_value['name'] . '_' . $i;

				foreach ($new_blocks as &$value) {
					if (isset($value['name']) && is_array($value['name'])) {
						$value['name'] = array_map(fn($item) => $item === $new_block_value['name'] ? $new_name : $item, $value['name']);
					}
				}

				unset($value);

				$new_block_value['name'] = $new_name;
				unset($names_in_new_block[$new_block_name]);
				$names_in_new_block[strtolower($new_name)] = true;
			}
		}

		unset($new_block_value);

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
}