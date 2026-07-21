<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\GlobalDataKeys;
use Kirki\App\DTO\Apps\AppDTO;
use Kirki\App\DTO\Apps\InstalledAppDTO;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\App\Supports\FileHandler;
use Kirki\App\Supports\FilterHooks;
use Kirki\Framework\Supports\Facades\DB;
use Kirki\Framework\Supports\Facades\File;
use Kirki\Framework\Supports\Facades\Http;
use Throwable;

use function Kirki\Framework\clean_path;
use function Kirki\Framework\collection;

class AppsService
{
    /**
     * @return AppDTO[]
     */
    public function get_all_apps()
    {
        $apps = Http::get(KIRKI_APPS_BASE_URL . '/apps.json');

        if ($apps->failed()) {
            throw new Exception(esc_html__('Failed to get apps.', 'kirki'));
        }

        return AppDTO::from_list($apps->json());
    }

    /**
     * @return InstalledAppDTO[]
     */
    public function get_installed_apps()
    {
        $apps = GlobalData::get(GlobalDataKeys::INSTALLED_APPS);

        return InstalledAppDTO::from_list(is_array($apps) ? $apps : []);
    }

    /**
     * @return array
     */
    public function get_app_settings(string $app_slug)
    {
        $settings = GlobalData::get(sprintf('%s%s', GlobalDataKeys::APP_SETTINGS, $app_slug)) ?? [];

		return FilterHooks::kirki_apps_configuration($app_slug, $settings);
    }

    /**
     * @param string $app_slug
     * @param array $settings
     * @return array
     */
    public function save_app_settings(string $app_slug, array $settings)
    {
        GlobalData::update(sprintf('%s%s', GlobalDataKeys::APP_SETTINGS, $app_slug), $settings);

        return $settings;
    }

    public function install_app(AppDTO $payload)
    {
        $app_slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $payload->slug);
        $src = $payload->src;
        $version = $payload->version;

        FileHandler::verify_directory_traversal($src);
        FileHandler::verify_directory_traversal($app_slug);

        $installed_apps = $this->get_installed_apps();

        $is_already_installed = collection($installed_apps)->some(function(InstalledAppDTO $app) use ($app_slug) {
            return $app->app_slug === $app_slug;
        });

        if ($is_already_installed) {
            throw new Exception(esc_html__('This app is already installed. Please check the kirki-apps directory in wp-content folders.', 'kirki'));
        }

        // Define the remote zip URL and generate a unique file name
		$remote_zip_url = KIRKI_APPS_BASE_URL . $src;
		$file_name_new  = uniqid('', true) . '.zip'; // 'random.ext'

        $zip_file_path = FileHandler::download_zip_from_remote($remote_zip_url, $file_name_new);

        if ($zip_file_path === false) {
            throw new Exception(esc_html__('Failed to download the app zip file. Please check the remote URL.', 'kirki'));
        }

        // Get the WordPress wp-content directory
        $base_upload_dir = WP_CONTENT_DIR; // Absolute path to 'uploads'
        $temp_folder_path     = clean_path($base_upload_dir . '/kirki-apps', false);

        if (!File::is_writable($base_upload_dir)) {
            File::delete($zip_file_path);
            throw new Exception(esc_html__('The wp-content directory is not writable. Please check folder permissions.', 'kirki'));
        }

        if (File::missing($base_upload_dir)) {
            if (!File::make_dir($temp_folder_path)) {
                File::delete($zip_file_path);
                throw new Exception(esc_html__('Failed to create directory: kirki-apps. Please check folder permissions.', 'kirki'));
            }
        }

        $result = FileHandler::extract_zip_file($zip_file_path, $temp_folder_path);

		if ($result === false) {
			File::delete($zip_file_path);
			throw new Exception(esc_html__('Failed to extract the app zip file. Please ensure the file is valid.', 'kirki'));
		}

        try {
            $installed_apps = collection($installed_apps)->push(InstalledAppDTO::from_array([
                'app_slug' => $app_slug,
                'version'  => $version,
            ]))->map(fn(InstalledAppDTO $app) => $app->to_array())->to_array();
    
            GlobalData::update(GlobalDataKeys::INSTALLED_APPS, $installed_apps);

            File::delete($zip_file_path);

            return [
                'app_slug' => $app_slug,
                'version'  => $version
            ];
        } catch (Throwable $throwable) {
            File::delete($zip_file_path);
            throw new Exception(
                sprintf(
                    /* translators: 1: error message */
                    esc_html__('An error occurred during the app installation: %s', 'kirki'), 
                    esc_html($throwable->getMessage())
                )
            );
        }
    }

    public function update_app(AppDTO $payload)
    {
        $app_slug    = preg_replace('/[^a-zA-Z0-9\-]/', '', $payload->slug);
		$src         = $payload->src;
		$new_version = $payload->version;

        FileHandler::verify_directory_traversal($src);
        FileHandler::verify_directory_traversal($app_slug);

        $installed_apps = $this->get_installed_apps();

        if (empty($installed_apps)) {
            throw new Exception(esc_html__('No apps are currently installed.', 'kirki'));
        }

        $is_installed = collection($installed_apps)->some(function(InstalledAppDTO $app) use ($app_slug) {
            return $app->app_slug === $app_slug;
        });

        if (!$is_installed) {
            throw new Exception(esc_html__('App not found in installed apps.', 'kirki'));
        }

        $base_upload_dir = WP_CONTENT_DIR;
        $apps_folder     = clean_path($base_upload_dir . '/kirki-apps', false);
        $app_folder      = clean_path($apps_folder . '/' . $app_slug, false);
        $backup_folder   = $app_folder . '_backup_' . time();

        // Create backup of current app
        if (!File::move($app_folder, $backup_folder)) {
            throw new Exception(esc_html__('Failed to create backup of current app version.', 'kirki'));
        }

        $remote_zip_url = KIRKI_APPS_BASE_URL . $src;
        $file_name_new  = uniqid( '', true ) . '.zip';
        $zip_file_path  = FileHandler::download_zip_from_remote($remote_zip_url, $file_name_new);

        if ($zip_file_path === false) {
            // Restore backup
            File::move($backup_folder, $app_folder);
            throw new Exception(esc_html__('Failed to download the new app version.', 'kirki'));
        }

        $result = FileHandler::extract_zip_file($zip_file_path, $apps_folder);

        File::delete($zip_file_path);

        if ($result === false) {
            // Restore backup
            File::move($backup_folder, $app_folder);
            throw new Exception(esc_html__('Failed to extract the new app version.', 'kirki'));
        }
        

        try {
            $installed_apps = collection($installed_apps)->map(function(InstalledAppDTO $app) use ($app_slug, $new_version) {
                if ($app->app_slug === $app_slug) {
                    $app->version = $new_version;
                }
    
                return $app->to_array();
            })->to_array();
    
            GlobalData::update(GlobalDataKeys::INSTALLED_APPS, $installed_apps);
        } catch (Throwable $throwable) {
            if (File::exists($app_folder)) {
                File::delete($app_folder);
            }

            if (File::exists($backup_folder)) {
                File::move($backup_folder, $app_folder);
            }

            throw new Exception(
                sprintf(
                    /* translators: 1: error message */
                    esc_html__('An error occurred during the app update: %s', 'kirki'), 
                    esc_html($throwable->getMessage())
                )
            );
        }

        File::delete($backup_folder);

        return [
            'app_slug' => $app_slug,
            'version'  => $new_version
        ];
    }

    public function delete_app(string $app_slug)
    {
        $installed_apps = $this->get_installed_apps();

        if (empty($installed_apps)) {
            throw new Exception(esc_html__('No apps are currently installed.', 'kirki'));
        }

        $is_installed = collection($installed_apps)->some(function(InstalledAppDTO $app) use ($app_slug) {
            return $app->app_slug === $app_slug;
        });

        if (!$is_installed) {
            throw new Exception(esc_html__('App not found in installed apps.', 'kirki'));
        }

        $base_upload_dir = WP_CONTENT_DIR; // Absolute path to 'uploads'
        $app_folder      = clean_path($base_upload_dir . '/kirki-apps/' . $app_slug, false);

        FileHandler::verify_directory_traversal($app_folder);

        $installed_apps = collection($installed_apps)->filter(function(InstalledAppDTO $app) use ($app_slug) {
            return $app->app_slug !== $app_slug;
        })->map(fn(InstalledAppDTO $app) => $app->to_array())->values()->to_array();

        DB::begin_transaction();

        try {
            GlobalData::update(GlobalDataKeys::INSTALLED_APPS, $installed_apps);
            $this->save_app_settings($app_slug, []);
            
            if (!File::delete($app_folder)) {
                throw new Exception(esc_html__('Failed to delete the app folder.', 'kirki'));
            }

            DB::commit();

            return true;
        } catch (Throwable $throwable) {
            DB::rollback();
            throw new Exception(
                sprintf(
                    /* translators: 1: error message */
                    esc_html__('An error occurred during the app deletion: %s', 'kirki'), 
                    esc_html($throwable->getMessage())
                )
            );
        }
    }
}