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

		$zip = new PclZip($zip_file_path);

		static::validate_zip_file($zip);

		$result = $zip->extract(
			PCLZIP_OPT_PATH,
			$destination_dir
		);

		if (is_array($result)) {
			return $result;
		}

		return false;
	}

	public static function validate_zip_file(PclZip $zip)
	{
		$list = $zip->listContent();

		if (!is_array($list)) {
			throw new Exception(esc_html__('Failed to read ZIP file.', 'kirki'));
		}

		foreach ($list as $entry) {
			 if (!isset($entry['filename'])) {
				throw new Exception(esc_html__('Invalid ZIP file.', 'kirki'));
			}

			static::validate_zip_entry($entry['filename']);
		}
	}

	private static function validate_zip_entry(string $entry_filename)
	{
		$entry_filename = clean_path($entry_filename, false);

		if (
			$entry_filename === '' ||
			str_contains($entry_filename, "\0") ||
			str_starts_with($entry_filename, '/') ||
			preg_match('/^[A-Za-z]:\//', $entry_filename)
		) {
			throw new Exception(esc_html__('Invalid ZIP file.', 'kirki'));
		}

		return static::verify_directory_traversal($entry_filename);
	}

	Public static function verify_directory_traversal(string $path) {
		if (preg_match('#(^|/)\.\.(/|$)#', clean_path($path, false))) {
			/* translators: %s: File Path */
			throw new Exception(sprintf(esc_html__('Directory traversal detected in %s.', 'kirki'), $path));
		}

		return true;
	}
}