<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Models\PageStage;
use Kirki\App\Models\PageStageData;
use Kirki\Framework\Supports\Facades\DB;
use Throwable;

/**
 * Moves old version history out of wp_postmeta and into the kirki_stages and
 * kirki_stage_data tables.
 *
 * - The published data stays in wp_postmeta, as before.
 * - Cron moves the pages in small batches in the background.
 * - A page that is opened before cron reaches it is moved right away (see migrate_post()).
 */
class PageStageMigrationService
{
    const STATUS_OPTION = 'kirki_stage_migration_status';
    const CURSOR_OPTION = 'kirki_stage_migration_cursor';
    const SWEEP_CURSOR_OPTION = 'kirki_stage_migration_sweep_cursor';
    const FAILED_OPTION = 'kirki_stage_migration_failed';
    const ATTEMPTS_OPTION = 'kirki_stage_migration_attempts';
    const LOCK_TRANSIENT = 'kirki_stage_migration_lock';
    const CRON_HOOK = 'kirki_migrate_stage_data';

    const STATUS_PENDING = 'pending';
    const STATUS_SWEEPING = 'sweeping';
    const STATUS_COMPLETE = 'complete';

    const LEGACY_VERSIONS_META_KEY = 'kirki_stage_versions';

    /** A page whose migration crashed the request this many times is skipped. */
    const MAX_ATTEMPTS = 3;

    /** @var int[] pages already moved in this request */
    protected static $migrated_in_request = [];

    /**
     * Start the migration and schedule the cron job.
     *
     * @return void
     */
    public function start()
    {
        // Nothing to move (new install, or staging never used), so it is done already.
        if (!$this->has_any_legacy_data()) {
            $this->finish();

            return;
        }

        // Status says "complete" but old data exists again: the plugin was downgraded and
        // updated again. Run the migration once more.
        if (!get_option(self::STATUS_OPTION) || $this->is_complete()) {
            update_option(self::STATUS_OPTION, self::STATUS_PENDING, true);
            update_option(self::CURSOR_OPTION, 0, false);
        }

        $this->schedule();
    }

    /**
     * Is there any old staging data left in wp_postmeta?
     *
     * @return bool
     */
    protected function has_any_legacy_data()
    {
        global $wpdb;

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s OR meta_key LIKE %s LIMIT 1",
                self::LEGACY_VERSIONS_META_KEY,
                $wpdb->esc_like('staged_') . '%'
            )
        );

        return !empty($found);
    }

    /**
     * Schedule the cron job again if the migration is not finished but the job is missing.
     *
     * The job can disappear, for example when the plugin was downgraded for a moment or a cron
     * cleaner plugin removed it. Without this check the migration would stop forever.
     * Runs on every request, but it is cheap: both values are already loaded by WordPress.
     *
     * @return void
     */
    public function ensure_scheduled()
    {
        $status = $this->get_status();

        if ($status === '' || $status === self::STATUS_COMPLETE) {
            return;
        }

        $this->schedule();
    }

    /**
     * @return void
     */
    public function schedule()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'kirki_stage_migration_interval', self::CRON_HOOK);
        }
    }

    /**
     * @return void
     */
    public function unschedule()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);

        while ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
        }
    }

    /**
     * @return string
     */
    public function get_status()
    {
        return (string) get_option(self::STATUS_OPTION, '');
    }

    /**
     * @return bool
     */
    public function is_complete()
    {
        return $this->get_status() === self::STATUS_COMPLETE;
    }

    /**
     * Move one batch of pages. Called by cron.
     *
     * @param int|null $batch_size
     *
     * @return array{processed:int, done:bool}
     */
    public function run_batch(?int $batch_size = null)
    {
        if ($this->is_complete()) {
            return ['processed' => 0, 'done' => true];
        }

        if (get_transient(self::LOCK_TRANSIENT)) {
            return ['processed' => 0, 'done' => false];
        }

        // No tables yet, so every page would fail. Wait until the tables exist.
        if (!$this->tables_exist()) {
            return ['processed' => 0, 'done' => false, 'error' => 'tables_missing'];
        }

        set_transient(self::LOCK_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

        try {
            $batch_size = $batch_size ?: (int) apply_filters('kirki_stage_migration_batch_size', 20);

            if ($this->get_status() === self::STATUS_SWEEPING) {
                return $this->sweep_orphans($batch_size);
            }

            global $wpdb;

            $cursor = (int) get_option(self::CURSOR_OPTION, 0);

            $post_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                     WHERE meta_key = %s AND post_id > %d
                     ORDER BY post_id ASC LIMIT %d",
                    self::LEGACY_VERSIONS_META_KEY,
                    $cursor,
                    $batch_size
                )
            );

            if (empty($post_ids)) {
                // All pages with a version list are done. Next: clean up leftover rows.
                update_option(self::STATUS_OPTION, self::STATUS_SWEEPING, true);
                update_option(self::SWEEP_CURSOR_OPTION, 0, false);

                return ['processed' => 0, 'done' => false];
            }

            $processed = 0;

            foreach ($post_ids as $post_id) {
                // Leave the rest for the next run, so one run never gets near the PHP limits.
                if ($processed > 0 && $this->is_over_budget()) {
                    break;
                }

                $this->migrate_post((int) $post_id);

                update_option(self::CURSOR_OPTION, (int) $post_id, false);

                ++$processed;
            }

            return ['processed' => $processed, 'done' => false];
        } finally {
            delete_transient(self::LOCK_TRANSIENT);
        }
    }

    /**
     * Does this page still have its old version list in wp_postmeta?
     *
     * @param int $page_id
     *
     * @return bool
     */
    public function has_legacy_data(int $page_id)
    {
        global $wpdb;

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
                $page_id,
                self::LEGACY_VERSIONS_META_KEY
            )
        );

        return !empty($found);
    }

    /**
     * Read one old meta value straight from the database.
     *
     * Never use get_post_meta() for old staging data. get_post_meta() loads every meta row of
     * the page into memory, and a page with a long history can have hundreds of MB of
     * staged_* rows. That crashed sites with "Allowed memory size exhausted" in 6.4.0.
     *
     * @param int $page_id
     * @param string $meta_key
     *
     * @return mixed '' when the key does not exist, like get_post_meta(..., true)
     */
    public function get_legacy_value(int $page_id, string $meta_key)
    {
        global $wpdb;

        $raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC LIMIT 1",
                $page_id,
                $meta_key
            )
        );

        return $raw === null ? '' : maybe_unserialize($raw);
    }

    /**
     * Move one page. Safe to call many times, and from two requests at once.
     *
     * - Cron and a page load can try the same page at the same time. A lock makes the second one
     *   wait. When it gets the lock it checks again, and if the page is already moved it stops.
     * - Everything runs in a transaction. If something fails, nothing is half written.
     * - The old data is deleted only at the very end, so a failed run simply starts over.
     *
     * @param int $page_id
     *
     * @return bool whether anything was migrated
     */
    public function migrate_post(int $page_id)
    {
        if (isset(self::$migrated_in_request[$page_id])) {
            return false;
        }

        if (!$this->acquire_post_lock($page_id)) {
            return false;
        }

        try {
            self::$migrated_in_request[$page_id] = true;

            // Read from the database, not the cache: another request may have moved this page
            // while we waited for the lock.
            $versions = $this->get_legacy_value($page_id, self::LEGACY_VERSIONS_META_KEY);

            if (empty($versions) || !is_array($versions)) {
                $this->delete_orphan_stage_meta($page_id);

                return false;
            }

            // A fatal error (e.g. out of memory) can not be caught, so count the attempt before
            // the work starts. A page that keeps crashing is skipped instead of taking the site
            // down on every request. Its old data stays and keeps working.
            if (!$this->record_attempt($page_id)) {
                return false;
            }

            // Copying a long history can take a while (about 12s for 180MB). Give it its own
            // time and memory, where the host allows it.
            wp_raise_memory_limit('kirki_stage_migration');
            if (function_exists('set_time_limit')) {
                @set_time_limit(300); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }

            DB::begin_transaction();

            try {
                $this->copy_post_to_tables($page_id, $versions);

                DB::commit();

                $this->set_failed($page_id, null);
                $this->clear_attempts($page_id);
            } catch (Throwable $throwable) {
                DB::rollback();

                clean_post_cache($page_id);

                $this->set_failed($page_id, $throwable->getMessage());

                error_log(sprintf('Kirki: staging data migration of post %d failed, it will be retried. %s', $page_id, $throwable->getMessage())); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

                return false;
            }

            clean_post_cache($page_id);

            return true;
        } finally {
            $this->release_post_lock($page_id);
        }
    }

    /**
     * Copy one page's old staging data into the tables, then delete the old data.
     *
     * @param int $page_id
     * @param array $versions legacy `kirki_stage_versions` value
     *
     * @return void
     */
    protected function copy_post_to_tables(int $page_id, array $versions)
    {
        // The old data is the truth. Rows already in the tables for this page are outdated
        // (left from before a downgrade, or from a failed run), so remove them first.
        $stage_ids = PageStage::query()->where('post_id', $page_id)->pluck('id')->to_array();

        if (!empty($stage_ids)) {
            PageStageData::query()->where_in('stage_id', array_map('intval', $stage_ids))->delete();
            PageStage::query()->where('post_id', $page_id)->delete();
        }

        $content_keys = PageMetaKeys::get_stage_content_keys();
        $obsolete_meta_keys = [self::LEGACY_VERSIONS_META_KEY];

        foreach ($versions as $version) {
            if (!is_array($version) || !isset($version['version'])) {
                continue;
            }

            $version_number = (int) $version['version'];
            $is_published = !empty($version['publish']);

            $stage = $this->insert_version_row($page_id, $version_number, $version, $is_published);

            foreach ($content_keys as $meta_key) {
                $legacy_meta_key = $this->get_legacy_meta_key($version_number, $meta_key);

                $obsolete_meta_keys[] = $legacy_meta_key;

                $value = $this->get_legacy_value($page_id, $legacy_meta_key);

                // Published version: its live data is in wp_postmeta. We also keep a copy in the
                // table, so the version still has its content after another version is published.
                // Take the copy from wp_postmeta. The old staged value is only used when the
                // published key is missing, and then it also fills that key. Never overwrite it.
                if ($is_published) {
                    $published_value = $this->get_legacy_value($page_id, $meta_key);

                    if (empty($published_value) && !empty($value)) {
                        $this->write_published_value($page_id, $meta_key, $value);
                    } elseif ($published_value !== '') {
                        $value = $published_value;
                    }
                }

                if ($value === '' || $value === false || $value === null) {
                    continue;
                }

                PageStageData::update_meta_value((int) $stage->id, $meta_key, $value);
            }
        }

        $this->delete_legacy_meta($page_id, array_unique($obsolete_meta_keys));
    }

    /**
     * Save a published value without update_post_meta(), which loads all meta of the page.
     *
     * @param int $page_id
     * @param string $meta_key
     * @param mixed $value
     *
     * @return void
     */
    protected function write_published_value(int $page_id, string $meta_key, $value)
    {
        global $wpdb;

        $meta_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
                $page_id,
                $meta_key
            )
        );

        if ($meta_id) {
            $wpdb->update($wpdb->postmeta, ['meta_value' => maybe_serialize($value)], ['meta_id' => (int) $meta_id]);
        } else {
            $wpdb->insert($wpdb->postmeta, ['post_id' => $page_id, 'meta_key' => $meta_key, 'meta_value' => maybe_serialize($value)]);
        }

        wp_cache_delete($page_id, 'post_meta');
    }

    /**
     * Delete old meta rows of a page in one query. delete_post_meta() runs several queries per
     * key, and a page can have thousands of old keys.
     *
     * @param int $page_id
     * @param string[] $meta_keys
     *
     * @return void
     */
    protected function delete_legacy_meta(int $page_id, array $meta_keys)
    {
        global $wpdb;

        foreach (array_chunk($meta_keys, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '%s'));

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key IN ($placeholders)", array_merge([$page_id], $chunk)));
        }

        wp_cache_delete($page_id, 'post_meta');
    }

    /**
     * Count one migration attempt of a page.
     *
     * @param int $page_id
     *
     * @return bool false when the page crashed too often and must be skipped
     */
    protected function record_attempt(int $page_id)
    {
        $attempts = get_option(self::ATTEMPTS_OPTION, []);
        $attempts = is_array($attempts) ? $attempts : [];
        $count = (int) ($attempts[$page_id] ?? 0);

        // Visitors and cron skip the page. A user who can edit it still tries, so the editor
        // never works on an empty version list while the real history waits in wp_postmeta.
        if ($count >= self::MAX_ATTEMPTS && !current_user_can('edit_post', $page_id)) {
            $this->set_failed($page_id, sprintf('Skipped after %d crashed attempts (out of memory or timeout).', $count));

            return false;
        }

        $attempts[$page_id] = $count + 1;
        update_option(self::ATTEMPTS_OPTION, $attempts, false);

        return true;
    }

    /**
     * @param int $page_id
     *
     * @return void
     */
    protected function clear_attempts(int $page_id)
    {
        $attempts = get_option(self::ATTEMPTS_OPTION, []);

        if (!is_array($attempts) || !isset($attempts[$page_id])) {
            return;
        }

        unset($attempts[$page_id]);

        if (empty($attempts)) {
            delete_option(self::ATTEMPTS_OPTION);
            return;
        }

        update_option(self::ATTEMPTS_OPTION, $attempts, false);
    }

    /**
     * Has this request used more than half of its memory or 20 seconds?
     *
     * @return bool
     */
    protected function is_over_budget()
    {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

        if ($memory_limit > 0 && memory_get_usage() > $memory_limit / 2) {
            return true;
        }

        return (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) > 20;
    }

    /**
     * Lock this page, so only one request moves it at a time. Waits if another request has it.
     *
     * MySQL frees the lock by itself when the request ends, so a crash never leaves it locked.
     *
     * @param int $page_id
     *
     * @return bool false when another process still holds the lock after the timeout
     */
    protected function acquire_post_lock(int $page_id)
    {
        global $wpdb;

        $timeout = (int) apply_filters('kirki_stage_migration_lock_timeout', 10);

        $acquired = $wpdb->get_var(
            $wpdb->prepare('SELECT GET_LOCK(%s, %d)', $this->get_post_lock_name($page_id), $timeout)
        );

        // NULL means the server has no lock support. Better to move without a lock than never.
        return $acquired === null || (int) $acquired === 1;
    }

    /**
     * @param int $page_id
     *
     * @return void
     */
    protected function release_post_lock(int $page_id)
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $this->get_post_lock_name($page_id)));
    }

    /**
     * Lock name for a page. It includes the database and table prefix, because lock names are
     * shared by every site on the MySQL server. Max 64 characters.
     *
     * @param int $page_id
     *
     * @return string
     */
    protected function get_post_lock_name(int $page_id)
    {
        global $wpdb;

        return 'kirki_stage_' . substr(md5(DB_NAME . $wpdb->prefix), 0, 12) . '_' . $page_id;
    }

    /**
     * @param int $page_id
     * @param int $version_number
     * @param array $version
     * @param bool $is_published
     *
     * @return PageStage
     */
    protected function insert_version_row(int $page_id, int $version_number, array $version, bool $is_published)
    {
        // The old list can have the same version twice. Reuse the row we already made.
        $existing = PageStage::query()
            ->where('post_id', $page_id)
            ->where('version', $version_number)
            ->first();

        if ($existing) {
            return $existing;
        }

        $created_on = !empty($version['created_on']) ? $version['created_on'] : current_time('mysql');
        $last_updated = !empty($version['last_updated']) ? $version['last_updated'] : $created_on;

        $stage = PageStage::create([
            'post_id' => $page_id,
            'version' => $version_number,
            'name' => isset($version['name']) ? (string) $version['name'] : '',
            'edited_by' => $this->resolve_legacy_editor_id($version),
            'is_published' => $is_published ? 1 : 0,
            'no_legacy_global_style' => !empty($version['no_legacy_global_style']) ? 1 : 0,
            'created_at' => $created_on,
            'updated_at' => $last_updated,
        ]);

        return $stage;
    }

    /**
     * Find the user id of the editor. Old entries only saved the login name in `edited_by`,
     * newer ones also saved the id in `edited_by_id`.
     *
     * @param array $version
     *
     * @return int
     */
    protected function resolve_legacy_editor_id(array $version)
    {
        if (!empty($version['edited_by_id'])) {
            return (int) $version['edited_by_id'];
        }

        $edited_by = $version['edited_by'] ?? null;

        if (is_numeric($edited_by)) {
            return (int) $edited_by;
        }

        if (is_string($edited_by) && $edited_by !== '') {
            $user = get_user_by('login', $edited_by);

            if ($user) {
                return (int) $user->ID;
            }
        }

        return 0;
    }

    /**
     * Delete old staged rows of a page that has no version list any more (nothing can use them).
     *
     * @param int $page_id
     *
     * @return void
     */
    protected function delete_orphan_stage_meta(int $page_id)
    {
        if (!apply_filters('kirki_stage_migration_delete_orphans', true)) {
            return;
        }

        global $wpdb;

        $meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_key FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key LIKE %s",
                $page_id,
                $wpdb->esc_like('staged_') . '%'
            )
        );

        $meta_keys = array_values(array_filter($meta_keys, [$this, 'is_legacy_stage_meta_key']));

        if (!empty($meta_keys)) {
            $this->delete_legacy_meta($page_id, $meta_keys);
        }
    }

    /**
     * Second pass: clean up pages that have old staged rows but no version list.
     *
     * @param int $batch_size
     *
     * @return array{processed:int, done:bool}
     */
    public function sweep_orphans(int $batch_size)
    {
        global $wpdb;

        // Uses its own cursor, so a page that fails is not picked again and again.
        // Anything left over is still moved when the page is opened.
        $cursor = (int) get_option(self::SWEEP_CURSOR_OPTION, 0);

        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key LIKE %s AND post_id > %d ORDER BY post_id ASC LIMIT %d",
                $wpdb->esc_like('staged_') . '%',
                $cursor,
                $batch_size
            )
        );

        $processed = 0;

        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;

            // This page got a version list after the first pass passed it, so move it normally.
            if ($this->has_legacy_data($post_id)) {
                unset(self::$migrated_in_request[$post_id]);

                $this->migrate_post($post_id);
            } else {
                $this->delete_orphan_stage_meta($post_id);
            }

            update_option(self::SWEEP_CURSOR_OPTION, $post_id, false);

            ++$processed;
        }

        if ($processed === 0) {
            $this->finish();

            return ['processed' => 0, 'done' => true];
        }

        return ['processed' => $processed, 'done' => false];
    }

    /**
     * @return bool
     */
    public function tables_exist()
    {
        return \Kirki\Manager\PluginActiveEvents::page_stage_tables_exist();
    }

    /**
     * Pages that failed to move, as [post id => error]. Their old data is kept, and they are
     * tried again when opened.
     *
     * @return array<int, string>
     */
    public function get_failed()
    {
        $failed = get_option(self::FAILED_OPTION, []);

        return is_array($failed) ? $failed : [];
    }

    /**
     * @param int $page_id
     * @param string|null $error null clears the entry
     *
     * @return void
     */
    protected function set_failed(int $page_id, ?string $error)
    {
        $failed = $this->get_failed();

        if ($error === null) {
            if (!isset($failed[$page_id])) {
                return;
            }

            unset($failed[$page_id]);
        } else {
            $failed[$page_id] = $error;
        }

        if (empty($failed)) {
            delete_option(self::FAILED_OPTION);
            return;
        }

        update_option(self::FAILED_OPTION, $failed, false);
    }

    /**
     * Mark the migration as done. Keep only the status option, delete the other helper options
     * and stop the cron job. Failed pages keep their old data and move when opened. The crash
     * counter is kept, so a page that crashed too often stays skipped.
     *
     * @return void
     */
    public function finish()
    {
        update_option(self::STATUS_OPTION, self::STATUS_COMPLETE, true);

        delete_option(self::CURSOR_OPTION);
        delete_option(self::SWEEP_CURSOR_OPTION);
        delete_option(self::FAILED_OPTION);
        delete_transient(self::LOCK_TRANSIENT);

        $this->unschedule();
    }

    /**
     * Start the migration again from the first page.
     *
     * @return void
     */
    public function reset()
    {
        update_option(self::STATUS_OPTION, self::STATUS_PENDING, true);
        update_option(self::CURSOR_OPTION, 0, false);
        update_option(self::SWEEP_CURSOR_OPTION, 0, false);
        delete_option(self::FAILED_OPTION);
        delete_option(self::ATTEMPTS_OPTION);

        self::$migrated_in_request = [];

        $this->schedule();
    }

    /**
     * Write all table data back into wp_postmeta in the old format. Use before downgrading.
     *
     * @return int number of pages written back
     */
    public function rollback()
    {
        $page_ids = PageStage::query()->pluck('post_id')->unique()->to_array();

        foreach ($page_ids as $page_id) {
            $page_id = (int) $page_id;

            $versions = [];
            $stages = PageStage::query()->where('post_id', $page_id)->order_by('version')->get();

            foreach ($stages as $stage) {
                $versions[] = [
                    'version' => (int) $stage->version,
                    'edited_by' => (int) $stage->edited_by,
                    'created_on' => $stage->created_at,
                    'last_updated' => $stage->updated_at,
                    'name' => $stage->name,
                    'publish' => (bool) $stage->is_published,
                    'no_legacy_global_style' => (bool) $stage->no_legacy_global_style,
                ];
            }

            update_post_meta($page_id, self::LEGACY_VERSIONS_META_KEY, $versions);

            foreach ($stages as $stage) {
                foreach ($stage->data()->get() as $row) {
                    update_post_meta(
                        $page_id,
                        $this->get_legacy_meta_key((int) $stage->version, $row->meta_key),
                        $row->meta_value
                    );
                }
            }

            clean_post_cache($page_id);
        }

        update_option(self::STATUS_OPTION, self::STATUS_PENDING, true);
        update_option(self::CURSOR_OPTION, 0, false);
        update_option(self::SWEEP_CURSOR_OPTION, 0, false);

        return count($page_ids);
    }

    /**
     * Old meta key for a version's data, e.g. staged_3_kirki.
     *
     * @param int $version_number
     * @param string $meta_key
     *
     * @return string
     */
    public function get_legacy_meta_key(int $version_number, string $meta_key)
    {
        return sprintf('staged_%s_%s', $version_number, $meta_key);
    }

    /**
     * @param string $meta_key
     *
     * @return bool
     */
    public function is_legacy_stage_meta_key(string $meta_key)
    {
        if (!preg_match('/^staged_\d+_(.+)$/', $meta_key, $matches)) {
            return false;
        }

        return in_array($matches[1], PageMetaKeys::get_stage_content_keys(), true);
    }
}
