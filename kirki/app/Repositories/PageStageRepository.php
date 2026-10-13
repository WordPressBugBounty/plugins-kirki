<?php

namespace Kirki\App\Repositories;

defined('ABSPATH') || exit;

use Kirki\App\Models\PageStage;
use Kirki\App\Models\PageStageData;
use Kirki\App\Services\PageStageMigrationService;
use Kirki\Framework\Collections\Collection;
use Throwable;

use function Kirki\Framework\collection;

/**
 * Reads and writes page versions and their data in the staging tables.
 *
 * Callers use (page id, version number, meta key). This class finds the version row in
 * kirki_stages, then reads its data through the `data` relation (stage_id).
 * The meta key is the same key the published data uses in wp_postmeta, e.g. `kirki`.
 */
class PageStageRepository
{
    /**
     * Version rows already loaded in this request, by page id.
     *
     * @var array<int, Collection>
     */
    protected $versions = [];

    /**
     * Version data already loaded in this request, by "{page_id}:{version}".
     *
     * @var array<string, array>
     */
    protected $values = [];

    /**
     * Pages already checked for old data in this request.
     *
     * @var array<int, bool>
     */
    protected $migration_checked = [];

    /**
     * All versions of a page, oldest first.
     *
     * @param int $page_id
     *
     * @return Collection<PageStage>
     */
    public function find_versions(int $page_id)
    {
        if (isset($this->versions[$page_id])) {
            return $this->versions[$page_id];
        }

        $this->migrate_post_if_needed($page_id);

        return $this->versions[$page_id] = $this->query_versions($page_id);
    }

    /**
     * @param int $page_id
     *
     * @return Collection<PageStage>
     */
    protected function query_versions(int $page_id)
    {
        $versions = PageStage::query()
            ->where('post_id', $page_id)
            ->order_by('version')
            ->get();

        return collection($versions instanceof Collection ? $versions->all() : $versions);
    }

    /**
     * If this page still has old data in wp_postmeta, move it now, before we read or write.
     * So a page works right away, even if cron has not moved it yet.
     *
     * We only check for the old version list, not whether the table is empty. After a downgrade
     * and update, a page can have both old rows in the table and newer old-format data, and the
     * newer data must win.
     *
     * @param int $page_id
     *
     * @return bool whether something was migrated
     */
    protected function migrate_post_if_needed(int $page_id)
    {
        if (isset($this->migration_checked[$page_id])) {
            return false;
        }

        $this->migration_checked[$page_id] = true;

        $migration = new PageStageMigrationService();

        if (!$migration->has_legacy_data($page_id) || !$migration->migrate_post($page_id)) {
            return false;
        }

        $this->forget_versions($page_id);
        $this->forget_page_values($page_id);

        return true;
    }

    /**
     * @param int $page_id
     * @param int $version
     *
     * @return PageStage|null
     */
    public function find_version(int $page_id, int $version)
    {
        return $this->find_versions($page_id)->first(
            fn ($item) => (int) $item->version === $version
        );
    }

    /**
     * @param int $page_id
     *
     * @return PageStage|null
     */
    public function find_published_version(int $page_id)
    {
        return $this->find_versions($page_id)->first(fn ($item) => (bool) $item->is_published);
    }

    /**
     * Highest version number of a page. 0 when the page has no version yet.
     *
     * @param int $page_id
     *
     * @return int
     */
    public function get_highest_version_number(int $page_id)
    {
        $versions = $this->find_versions($page_id);

        if ($versions->is_empty()) {
            return 0;
        }

        $highest = 0;

        foreach ($versions as $version) {
            $highest = max($highest, (int) $version->version);
        }

        return $highest;
    }

    /**
     * @param int $page_id
     * @param array $attributes
     *
     * @return PageStage
     */
    public function create_version(int $page_id, array $attributes)
    {
        try {
            $stage = PageStage::create(array_merge($attributes, ['post_id' => $page_id]));
        } catch (Throwable $throwable) {
            // Another request made this version first (the editor saves each part of the page in
            // its own request, all at the same time). Just use that version.
            $stage = PageStage::query()
                ->where('post_id', $page_id)
                ->where('version', (int) ($attributes['version'] ?? 0))
                ->first();

            if (!$stage) {
                throw $throwable;
            }
        }

        $this->forget_versions($page_id);

        return $stage;
    }

    /**
     * Run the callback while this page is locked, so only one request creates a version at a time.
     *
     * Why: a new version gets "highest number + 1". The editor sends several save requests at the
     * same time, and without the lock each of them would create the same new version.
     * The callback must read the versions again, another request may have just added one.
     *
     * MySQL frees the lock when the request ends, so a crash never leaves the page locked.
     * If the lock takes too long, the callback runs anyway and create_version() handles it.
     *
     * @param int $page_id
     * @param callable $callback
     *
     * @return mixed the callback result
     */
    public function with_version_lock(int $page_id, callable $callback)
    {
        global $wpdb;

        $lock_name = 'kirki_stage_version_' . substr(md5(DB_NAME . $wpdb->prefix), 0, 12) . '_' . $page_id;
        $timeout = (int) apply_filters('kirki_stage_version_lock_timeout', 10);

        $acquired = (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $lock_name, $timeout)) === 1;

        try {
            $this->forget_versions($page_id);

            return $callback();
        } finally {
            if ($acquired) {
                $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
            }
        }
    }

    /**
     * @param int $page_id
     * @param int $version
     * @param array $attributes
     *
     * @return bool
     */
    public function update_version(int $page_id, int $version, array $attributes)
    {
        $updated = PageStage::query()
            ->where('post_id', $page_id)
            ->where('version', $version)
            ->update($attributes);

        $this->forget_versions($page_id);

        return (bool) $updated;
    }

    /**
     * Mark one version as published and unpublish every other version of the page.
     *
     * @param int $page_id
     * @param int $version
     *
     * @return void
     */
    public function set_published(int $page_id, int $version)
    {
        PageStage::query()
            ->where('post_id', $page_id)
            ->update(['is_published' => 0]);

        PageStage::query()
            ->where('post_id', $page_id)
            ->where('version', $version)
            ->update(['is_published' => 1]);

        $this->forget_versions($page_id);
    }

    /**
     * Delete a version row and its payloads.
     *
     * @param int $page_id
     * @param int $version
     *
     * @return void
     */
    public function delete_version(int $page_id, int $version)
    {
        // Delete the data ourselves too, in case the database has no foreign key to do it.
        $this->delete_values($page_id, $version);

        PageStage::query()
            ->where('post_id', $page_id)
            ->where('version', $version)
            ->delete();

        $this->forget_versions($page_id);
    }

    /**
     * @param int $page_id
     * @param int $version
     * @param string $meta_key published style meta key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get_value(int $page_id, int $version, string $meta_key, $default = null)
    {
        $values = $this->get_all_values($page_id, $version);

        return array_key_exists($meta_key, $values) && $values[$meta_key] !== null && $values[$meta_key] !== ''
            ? $values[$meta_key]
            : $default;
    }

    /**
     * @param int $page_id
     * @param int $version
     * @param array $meta_keys
     *
     * @return array<string, mixed> keyed by meta key, missing keys omitted
     */
    public function get_values(int $page_id, int $version, array $meta_keys)
    {
        $values = $this->get_all_values($page_id, $version);

        return array_intersect_key($values, array_flip($meta_keys));
    }

    /**
     * @param int $page_id
     * @param int $version
     * @param string $meta_key
     * @param mixed $value
     *
     * @return void
     */
    public function set_value(int $page_id, int $version, string $meta_key, $value)
    {
        $stage = $this->find_version($page_id, $version);

        // The version must exist before we save data for it. Data for a missing version could
        // never be shown or read, so skip it and log it.
        if (!$stage) {
            error_log(sprintf('Kirki: staged "%s" of post %d not saved, version %d does not exist.', $meta_key, $page_id, $version)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            return;
        }

        PageStageData::update_meta_value((int) $stage->id, $meta_key, $value);

        $this->values[$this->value_cache_key($page_id, $version)][$meta_key] = $value;
    }

    /**
     * Copy payloads from one version to another.
     *
     * @param int $page_id
     * @param int $from_version
     * @param int $to_version
     * @param array $meta_keys
     *
     * @return void
     */
    public function copy_values(int $page_id, int $from_version, int $to_version, array $meta_keys)
    {
        $values = $this->get_values($page_id, $from_version, $meta_keys);

        foreach ($values as $meta_key => $value) {
            $this->set_value($page_id, $to_version, $meta_key, $value);
        }
    }

    /**
     * @param int $page_id
     * @param int $version
     * @param array|null $meta_keys null deletes every payload of the version
     *
     * @return void
     */
    public function delete_values(int $page_id, int $version, ?array $meta_keys = null)
    {
        if (is_array($meta_keys) && empty($meta_keys)) {
            return;
        }

        $stage = $this->find_version($page_id, $version);

        if ($stage) {
            $query = $stage->data();

            if (!is_null($meta_keys)) {
                $query->where_in('meta_key', $meta_keys);
            }

            $query->delete();
        }

        $this->forget_values($page_id, $version);
    }

    /**
     * Delete all versions of a page and their data.
     *
     * @param int $page_id
     *
     * @return void
     */
    public function delete_for_post(int $page_id)
    {
        $stage_ids = PageStage::query()->where('post_id', $page_id)->pluck('id')->to_array();

        if (!empty($stage_ids)) {
            PageStageData::query()->where_in('stage_id', array_map('intval', $stage_ids))->delete();
            PageStage::query()->where('post_id', $page_id)->delete();
        }

        $this->forget_versions($page_id);
        $this->forget_page_values($page_id);
    }

    /**
     * @param int $page_id
     *
     * @return void
     */
    protected function forget_page_values(int $page_id)
    {
        foreach (array_keys($this->values) as $key) {
            if (strpos($key, $page_id . ':') === 0) {
                unset($this->values[$key]);
            }
        }
    }

    /**
     * @param int $page_id
     *
     * @return void
     */
    public function forget_versions(int $page_id)
    {
        unset($this->versions[$page_id]);
    }

    /**
     * All data of one version, as [meta key => value].
     *
     * @param int $page_id
     * @param int $version
     *
     * @return array<string, mixed>
     */
    protected function get_all_values(int $page_id, int $version)
    {
        $this->migrate_post_if_needed($page_id);

        $cache_key = $this->value_cache_key($page_id, $version);

        if (isset($this->values[$cache_key])) {
            return $this->values[$cache_key];
        }

        $stage = $this->find_version($page_id, $version);

        $values = [];

        if ($stage) {
            foreach ($stage->data()->get() as $row) {
                $values[$row->meta_key] = $row->meta_value;
            }
        }

        return $this->values[$cache_key] = $values;
    }

    /**
     * @param int $page_id
     * @param int $version
     *
     * @return void
     */
    protected function forget_values(int $page_id, int $version)
    {
        unset($this->values[$this->value_cache_key($page_id, $version)]);
    }

    /**
     * @param int $page_id
     * @param int $version
     *
     * @return string
     */
    protected function value_cache_key(int $page_id, int $version)
    {
        return $page_id . ':' . $version;
    }
}
