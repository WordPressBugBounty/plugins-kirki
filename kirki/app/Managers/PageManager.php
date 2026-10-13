<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\KirkiDateTimeFormat;
use Kirki\App\Constants\OptionKeys;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Models\PageStage;
use Kirki\App\Models\PostMeta;
use Kirki\App\Repositories\PageStageRepository;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Supports\Facades\Option;
use Kirki\Framework\Constants\DateTimeFormats;
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

	/** @var PageStageRepository */
	protected $stages;


	public function __construct(?PageStageRepository $stages = null)
	{
		$this->staged_versions = collection();
		$this->stages = $stages ?: new PageStageRepository();
	}

	/**
	 * Where page versions and their data are saved.
	 *
	 * @return PageStageRepository
	 */
	public function stages()
	{
		return $this->stages;
	}

	/**
	 * Read a page value for a version.
	 *
	 * Published version: read from wp_postmeta. Other versions: read from the
	 * kirki_stage_data table. Both use the same $meta_key.
	 *
	 * @param int $page_id
	 * @param string $meta_key
	 * @param int|false $stage_version
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function read_page_meta(int $page_id, string $meta_key, $stage_version = false, $default = null)
	{
		$stage_version = $this->resolve_stage_version($page_id, $stage_version);

		if ($this->is_published_stage_version($page_id, $stage_version)) {
			return PostMeta::get_meta_value($page_id, $meta_key, $default);
		}

		return $this->stages->get_value($page_id, (int) $stage_version, $meta_key, $default);
	}

	/**
	 * Save a page value for a version.
	 *
	 * @param int $page_id
	 * @param string $meta_key
	 * @param mixed $value
	 * @param int|false $stage_version
	 *
	 * @return void
	 */
	public function write_page_meta(int $page_id, string $meta_key, $value, $stage_version = false)
	{
		// No version means "save the published value" (wp_postmeta).
		// Do not look up a version here: saving must never create a new version.
		if (!$stage_version || $this->is_published_stage_version($page_id, $stage_version)) {
			PostMeta::update_meta_value($page_id, $meta_key, $value);
			return;
		}

		$this->stages->set_value($page_id, (int) $stage_version, $meta_key, $value);
	}

	/**
	 * Use the given version, or the latest version when none is given (same as before).
	 *
	 * @param int $page_id
	 * @param int|false $stage_version
	 *
	 * @return int|false
	 */
	protected function resolve_stage_version(int $page_id, $stage_version)
	{
		if ($stage_version) {
			return (int) $stage_version;
		}

		$stage_must = false;

		return $this->get_most_recent_stage_version($page_id, $stage_must);
	}

	/**
	 * Is this the published version? (Its data is in wp_postmeta.)
	 *
	 * @param int $page_id
	 * @param int|false $stage_version
	 *
	 * @return bool
	 */
	protected function is_published_stage_version(int $page_id, $stage_version)
	{
		if (!$stage_version) {
			return true;
		}

		$published_version = $this->get_published_stage_version($page_id);

		return $published_version && (int) $published_version === (int) $stage_version;
	}

	/**
	 * Turn a version row into the array the editor and REST API expect.
	 *
	 * @param \Kirki\App\Models\PageStage $stage
	 *
	 * @return array
	 */
	protected function format_stage_version($stage)
	{
		$edited_by_id = (int) $stage->edited_by;

		return [
			'version' => (int) $stage->version,
			'edited_by_id' => $edited_by_id,
			'edited_by' => $edited_by_id ? user($edited_by_id)->get_display_name() : '',
			'created_on' => $stage->created_at,
			'last_updated' => $stage->updated_at,
			'name' => $stage->name,
			'publish' => (bool) $stage->is_published,
			'no_legacy_global_style' => (bool) $stage->no_legacy_global_style,
		];
	}

	/**
	 * Load the versions of a page again from the database.
	 *
	 * @param int $page_id
	 *
	 * @return Collection
	 */
	protected function refresh_staged_versions(int $page_id)
	{
		$this->stages->forget_versions($page_id);

		return $this->staged_versions = $this->stages
			->find_versions($page_id)
			->map(fn ($stage) => $this->format_stage_version($stage));
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
		$this->write_page_meta($page_id, PageMetaKeys::STYLE_BLOCKS, $data, $staging_version);
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
		$this->write_page_meta($page_id, PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED, $data, $staging_version);
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
		$this->write_page_meta($page_id, PageMetaKeys::USED_GLOBAL_STYLE_BLOCK_IDS, $data, $staging_version);
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
		$this->write_page_meta($page_id, PageMetaKeys::USED_STYLE_BLOCK_IDS, $data, $staging_version);
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
		$this->write_page_meta($page_id, PageMetaKeys::USED_FONT_LIST, $data, $staging_version);
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
		$this->write_page_meta($page_id, PageMetaKeys::BLOCKS, $data, $staging_version);
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

		if ($staged_versions->count() === 0) {
			return false;
		}

		$last_version = $staged_versions->last();

		$this->stages->update_version($page_id, (int) $last_version['version'], [
			'updated_at' => Date::now(get_timezone(true))->format(DateTimeFormats::DB_DATETIME),
			'no_legacy_global_style' => $has_legacy_global_style ? 0 : 1,
		]);

		return $this->refresh_staged_versions($page_id);
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
		[$version_number, $recent_stage_version] = $this->find_recent_stage_version($page_id);

		if (!$this->needs_new_stage_version($version_number, $recent_stage_version, $stage_must, $restoring)) {
			return (int) $version_number;
		}

		// The editor sends several save requests at the same time. Only the first one creates
		// the new version. The others wait for the lock, see the new version and use it.
		return (int) $this->stages->with_version_lock($page_id, function () use ($page_id, $stage_must, $restoring, $old_version) {
			$this->staged_versions = collection();

			[$version_number, $recent_stage_version, $being_restored] = $this->find_recent_stage_version($page_id, $restoring, $old_version);

			if (!$this->needs_new_stage_version($version_number, $recent_stage_version, $stage_must, $restoring)) {
				return (int) $version_number;
			}

			return $this->add_stage_version($page_id, $version_number + 1, [], $being_restored);
		});
	}

	/**
	 * Get the version an editor save (auto save or Save button) must write to.
	 *
	 * - Latest version is not published: use it.
	 * - Latest version is published and the value is the same as the published one: return false,
	 *   nothing changed. (The editor auto saves right after a publish. Without this check that
	 *   save would add a new version that is only a copy of the published one.)
	 * - Latest version is published and the value is new: create a new version. It starts as a
	 *   copy of the published data, so the parts of the page that did not change are in it too.
	 *
	 * The editor saves each part of the page in its own request, all at the same time. The lock
	 * makes them run one by one here, so every request sees the complete new version.
	 *
	 * @param int $page_id
	 * @param string $meta_key
	 * @param mixed $value
	 *
	 * @return int|false
	 */
	public function get_stage_version_for_save(int $page_id, string $meta_key, $value)
	{
		return $this->stages->with_version_lock($page_id, function () use ($page_id, $meta_key, $value) {
			$this->staged_versions = collection();

			$version_id = $this->get_most_recent_stage_version($page_id, false);

			if (!$this->is_published_stage_version($page_id, $version_id)) {
				return $version_id;
			}

			if ($this->is_same_page_meta($value, get_post_meta($page_id, $meta_key, true))) {
				return false;
			}

			$new_version_id = $this->add_stage_version($page_id, $version_id + 1);

			$this->snapshot_published_data($page_id, $new_version_id);

			return $new_version_id;
		});
	}

	/**
	 * Get the latest version of a page, and the version being restored (when restoring).
	 *
	 * @param int $page_id
	 * @param bool $restoring
	 * @param int|bool $old_version
	 *
	 * @return array{0:int, 1:array|false, 2:array|false}
	 */
	protected function find_recent_stage_version(int $page_id, $restoring = false, $old_version = false)
	{
		$staged_versions = $this->get_all_staged_versions($page_id);
		$recent_stage_version = false;
		$version_number = 0;
		$being_restored = false;

		foreach ($staged_versions as $version) {
			if (is_array($version) && intval($version['version']) > $version_number) {
				$version_number = (int) $version['version'];
				$recent_stage_version = $version;
			}

			if ($restoring && intval($old_version) === intval($version['version'])) {
				$being_restored = $version;
			}
		}

		return [$version_number, $recent_stage_version, $being_restored];
	}

	/**
	 * @param int $version_number
	 * @param array|false $recent_stage_version
	 * @param bool $stage_must
	 * @param bool $restoring
	 *
	 * @return bool
	 */
	protected function needs_new_stage_version($version_number, $recent_stage_version, $stage_must, $restoring)
	{
		return $version_number === 0
			|| ($stage_must && isset($recent_stage_version['publish']) && $recent_stage_version['publish'])
			|| $restoring;
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
		$stages = $this->stages->find_versions($page_id);

		if ($stages->is_empty()) {
			// A version needs a real post (foreign key), so do not create one for a missing post.
			if (!$create_if_empty || !get_post($page_id)) {
				return $this->staged_versions = collection();
			}

			return $this->create_first_stage_version_if_empty($page_id);
		}

		return $this->staged_versions = $stages->map(fn ($stage) => $this->format_stage_version($stage));
	}

	/**
	 * Create first stage version
	 * 
	 * @param int $page_id
	 * @return Collection
	 */
	private function create_first_stage_version_if_empty(int $page_id)
	{
		return $this->stages->with_version_lock($page_id, function () use ($page_id) {
			// Another request may have created it while we waited for the lock.
			$stages = $this->stages->find_versions($page_id);

			if (!$stages->is_empty()) {
				return $this->staged_versions = $stages->map(fn ($stage) => $this->format_stage_version($stage));
			}

			// When stage version is empty, it means there is legacy global style blocks
			$has_legacy_global_style = true;

			// The first version is the published one. Publishing it also saves a copy of the
			// published data for it, so it keeps its content after another version is published.
			$this->add_stage_version($page_id, 1, [], false, $has_legacy_global_style);

			return $this->publish_stage_version($page_id, $has_legacy_global_style);
		});
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
			$has_legacy_global_style = empty($being_restored['no_legacy_global_style']);
		}

		$this->stages->create_version($page_id, [
			'version' => $version_number,
			'name' => $version_name,
			'edited_by' => user()->get_id(),
			'is_published' => 0,
			'no_legacy_global_style' => $has_legacy_global_style ? 0 : 1,
			'created_at' => $datetime,
			'updated_at' => $datetime,
		]);

		$this->refresh_staged_versions($page_id);

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

		// The editor has already saved the new data to the published keys. When the latest version
		// is the published one (no auto save made a new version yet), publishing into it would
		// replace its saved copy and the old version would be lost from the history.
		// So the new data gets its own version.
		if ($this->is_published_stage_version($page_id, $version_id) && $this->published_data_changed($page_id, $version_id)) {
			$version_id = $this->add_stage_version($page_id, $version_id + 1);
		}

		// Fill any empty published keys from this version's data.
		$this->promote_stage_version_data($page_id, $version_id);

		$this->stages->set_published($page_id, $version_id);

		$this->snapshot_published_data($page_id, $version_id);

		PageStage::query()
			->where('post_id', $page_id)
			->update(['no_legacy_global_style' => $has_legacy_global_style ? 0 : 1]);

		return $this->refresh_staged_versions($page_id);
	}

	/**
	 * Is the published data (wp_postmeta) different from the copy saved for this version?
	 *
	 * @param int $page_id
	 * @param int $version_id
	 *
	 * @return bool
	 */
	protected function published_data_changed(int $page_id, int $version_id)
	{
		$meta_keys = PageMetaKeys::get_stage_content_keys();

		// The version has no saved copy (the empty first version of a new page), so there is
		// nothing to keep. The first publish goes into it.
		if (empty($this->stages->get_values($page_id, $version_id, $meta_keys))) {
			return false;
		}

		foreach ($meta_keys as $meta_key) {
			$published_value = get_post_meta($page_id, $meta_key, true);

			if ($published_value === '') {
				continue;
			}

			$saved_value = $this->stages->get_value($page_id, $version_id, $meta_key, '');

			if (!$this->is_same_page_meta($published_value, $saved_value)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Two empty values are the same: an empty list is saved as '' in some places and [] in others.
	 *
	 * @param mixed $first
	 * @param mixed $second
	 *
	 * @return bool
	 */
	protected function is_same_page_meta($first, $second)
	{
		return (empty($first) && empty($second)) || maybe_serialize($first) === maybe_serialize($second);
	}

	/**
	 * Copy this version's data into the published keys, but only into keys that are empty.
	 * 
	 * The editor saves the page to the published keys right before it calls publish, so the
	 * published keys already have the newest data. We never overwrite them. We only fill keys
	 * the editor did not save, so the published page is complete.
	 * 
	 * @param int $page_id
	 * @param int $version_id
	 * 
	 * @return void
	 */
	protected function promote_stage_version_data(int $page_id, int $version_id)
	{
		$meta_keys = PageMetaKeys::get_stage_content_keys();

		$staged_data = $this->stages->get_values($page_id, $version_id, $meta_keys);

		foreach ($staged_data as $meta_key => $value) {
			$published_value = PostMeta::get_meta_value($page_id, $meta_key);

			if (!empty($published_value) || empty($value)) {
				continue;
			}

			PostMeta::update_meta_value($page_id, $meta_key, $value);
		}
	}

	/**
	 * Save a copy of the published data for the published version.
	 *
	 * wp_postmeta only has the current published data. When another version is published later,
	 * this version is read from its own rows, so they must hold what was published.
	 * (The old code kept staged_{version}_* copies for the same reason.)
	 *
	 * @param int $page_id
	 * @param int $version_id
	 *
	 * @return void
	 */
	protected function snapshot_published_data(int $page_id, int $version_id)
	{
		foreach (PageMetaKeys::get_stage_content_keys() as $meta_key) {
			$value = get_post_meta($page_id, $meta_key, true);

			// Key not saved at all, nothing to copy.
			if ($value === '') {
				continue;
			}

			$this->stages->set_value($page_id, $version_id, $meta_key, $value);
		}
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
		$this->stages->update_version($page_id, $version_id, ['name' => $new_name]);

		return $this->refresh_staged_versions($page_id);
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
		$version_info = $this->get_staged_version_info($page_id, $version_id);

		// Never delete the published version.
		if (is_array($version_info) && isset($version_info['publish']) && is_truthy($version_info['publish'])) {
			return $this->refresh_staged_versions($page_id);
		}

		$this->stages->delete_version($page_id, $version_id);

		return $this->refresh_staged_versions($page_id);
	}

	protected function restore_page_meta(string $meta_key,int $page_id, int $old_version_id, int $new_version_id)
	{
		$old_data = $this->read_page_meta($page_id, $meta_key, $old_version_id);

		$this->write_page_meta($page_id, $meta_key, $old_data, $new_version_id);
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

		foreach(PageMetaKeys::get_stage_content_keys() as $meta_key) {
			$this->restore_page_meta($meta_key, $page_id, $old_version_id, $new_version_id);
		}

		return $new_version_id;
	}

	/**
	 * Get the staged meta name
	 * 
	 * @deprecated Version data is not in wp_postmeta any more, so this key points to nothing.
	 *             Use read_page_meta()/write_page_meta(). Kept so other plugins do not break.
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

		$global_stage_style_blocks = $this->read_page_meta($page_id, PageMetaKeys::GLOBAL_STYLE_BLOCK_DEPRECATED, $stage_version, []);

		if ($global_stage_style_blocks) {
			$staging_style_blocks = $global_stage_style_blocks;
		}

		$current_stage_style_blocks = $this->read_page_meta($page_id, PageMetaKeys::STYLE_BLOCKS, $stage_version, []);

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

		$current_stage_style_blocks = $this->read_page_meta($page_id, PageMetaKeys::STYLE_BLOCKS, $stage_version, []);

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