<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Exception;
use Kirki\Framework\Supports\Facades\File as FileHelper;
use Kirki\Framework\Supports\Facades\Http;
use PclZip;

use function Kirki\App\get_upload_directory;
use function Kirki\Framework\clean_path;
use function Kirki\Framework\Polyfill\array_last;

class FileHandler 
{
	public static function get_temp_folder_path()
	{
		$temp_folder = 'kirki_temp';
		
		return get_upload_directory() . '/' . $temp_folder;
	}

	/**
	 * Download zip file from remote server
	 * 
	 * @param string $remote_file_url
	 * @param string $file_name
	 * @return string|false -- if failed return false
	 */
    public static function download_zip_from_remote(string $remote_file_url, string $file_name)
    {
        $file_ext = explode('.', $remote_file_url); // ['file', 'ext']
		$file_ext = strtolower(array_last($file_ext)); // 'ext'
		$allowed = ['zip'];

		if (!in_array($file_ext, $allowed)) {
			return false;
		}

		// Download the file from the remote server.
		$response = Http::timeout(120)
			->with_options([
				'redirection' => 0
			])
			->with_user_agent('WordPress')
			->get($remote_file_url);

		if ($response->failed()) {
			return false;
		}

		// Save the file locally.
		// Local path to save the downloaded file.
		$local_file_path = clean_path(get_upload_directory() . '/' . $file_name, false);

		static::verify_directory_traversal($local_file_path);
		
		$is_downloaded = FileHelper::put($local_file_path, $response->body());

		if (!$is_downloaded) {
			return false;
		}
		
		return $local_file_path;
    }

	/**
	 * @return array|false 
	 * return false on failure
	 */
	public static function extract_zip_file(string $zip_file_path, string $destination_dir)
	{
		if (!class_exists('PclZip')) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		}

		$zip_file_path = clean_path($zip_file_path, false);

		if (FileHelper::missing($zip_file_path)) {
			return false;
		}

		if (!FileHelper::is_directory($destination_dir)) {
			FileHelper::make_dir($destination_dir);
		}

		static::verify_directory_traversal($destination_dir);

		$zip = new PclZip($zip_file_path);

		$result = $zip->extract(
			PCLZIP_OPT_PATH,
			$destination_dir
		);

		if (is_array($result)) {
			return $result;
		}

		return false;
	}

	Public static function verify_directory_traversal(string $path) {
		if (preg_match('#(^|/)\.\.(/|$)#', clean_path($path, false))) {
			/* translators: %s: File Path */
			throw new Exception(esc_html__(sprintf('Directory traversal detected in %s.', $path), 'kirki'));
		}
	}
}