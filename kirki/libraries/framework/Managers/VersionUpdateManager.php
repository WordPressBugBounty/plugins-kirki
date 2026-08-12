<?php

/**
 * Version manager.
 *
 * @package    Framework
 * @subpackage Managers
 * @since      1.0.0
 */
namespace Kirki\Framework\Managers;

\defined('ABSPATH') || exit;
use Kirki\Framework\Constants\OptionKeys;
use Kirki\Framework\Contracts\Executor;
use Kirki\Framework\Supports\Facades\Option;
use function Kirki\Framework\app;
class VersionUpdateManager implements Executor
{
    /**
     * The instance of the VersionManager.
     *
     * @var static
     * 
     * @since 1.0.0
     */
    protected static $instance;
    /**
     * The version details.
     *
     * @var array
     * 
     * @since 1.0.0
     */
    protected $version_details = [];
    /**
     * The installed version key.
     *
     * @var string
     * 
     * @since 1.0.0
     */
    protected $installed_version_key = '';
    /**
     * Constructor.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->installed_version_key = \sprintf('%s%s', app()->prefix(), OptionKeys::INSTALLED_VERSION);
    }
    /**
     * Get the instance of the VersionManager.
     *
     * @return VersionUpdateManager
     *
     * @since 1.0.0
     */
    public static function get_instance()
    {
        if (static::$instance) {
            return static::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }
    /**
     * Execute the version manager.
     * 
     * @return void
     *
     * @since 1.0.0
     */
    public function run()
    {
        if ($this->is_current_version_already_installed()) {
            return;
        }
        $updates_path = app()->config_path('version-updates.php');
        if (!\file_exists($updates_path)) {
            return;
        }
        $plugin_updates = (include $updates_path);
        $plugin_update_keys = \array_keys($plugin_updates);
        \usort($plugin_update_keys, function ($first, $second) {
            return \version_compare($first, $second);
        });
        $installed_versions = $this->get_installed_versions();
        foreach ($plugin_update_keys as $version) {
            if (\version_compare($version, app()->version(), '<=') && !$this->is_already_installed($version)) {
                $callback = $plugin_updates[$version];
                $callback();
                $installed_versions[] = $version;
            }
        }
        $this->update_installed_version($installed_versions);
    }
    /**
     * Get the installed version details.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function get_installed_version_details()
    {
        if (!empty($this->version_details)) {
            return $this->version_details;
        }
        $default = ['current_version' => '0.0.0', 'installed_versions' => []];
        $version_details = Option::get($this->installed_version_key, $default);
        // For backward compatibility check. If the option is not set or is not an array, set it to the default.
        if (!\is_array($version_details) || !isset($version_details['installed_versions'], $version_details['current_version'])) {
            $version_details = $default;
        }
        $this->version_details = $version_details;
        return $this->version_details;
    }
    /**
     * Get the current installed version.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function get_current_installed_version()
    {
        return $this->get_installed_version_details()['current_version'];
    }
    /**
     * Get the installed versions.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function get_installed_versions()
    {
        return $this->get_installed_version_details()['installed_versions'];
    }
    /**
     * Update the installed version.
     *
     * @param array $installed_versions The installed versions.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function update_installed_version($installed_versions = [])
    {
        $installed_versions = \array_unique($installed_versions);
        \usort($installed_versions, function ($first, $second) {
            return \version_compare($first, $second);
        });
        $version_details = ['current_version' => app()->version(), 'installed_versions' => $installed_versions];
        $is_updated = Option::set($this->installed_version_key, $version_details, \true);
        if (!empty($is_updated)) {
            $this->version_details = $version_details;
            return \true;
        }
        return \false;
    }
    /**
     * Check if the current version is already installed.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_current_version_already_installed()
    {
        return (bool) \version_compare($this->get_current_installed_version(), app()->version(), '>=');
    }
    /**
     * Check if the version is already installed.
     *
     * @param string $version The version to check.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_already_installed(string $version)
    {
        $installed_versions = $this->get_installed_versions();
        return \in_array($version, $installed_versions, \true);
    }
}
