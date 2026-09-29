<?php

/**
 * Manage dynamic form data api calls
 *
 * @package kirki
 */

namespace Kirki\Ajax;

use Kirki\HelperFunctions;
use Kirki\Ajax\Symbol;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class ExportImport
{


	public static function export()
	{
		$filename = HelperFunctions::sanitize_text(isset($_POST['filename']) ? $_POST['filename'] : '');
		$filename = basename($filename);
		$data = isset($_POST['data']) ? $_POST['data'] : '{}';
		$data = json_decode(stripslashes($data), true);
		$blocks = $data['blocks'];
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];

		$asset_urls = array();
		$symbol_ids = array();
		$symbols = array();

		foreach ($blocks as $key => $block) {
			if (isset($block['properties']['symbolId'])) {
				$symbol_id = array(
					'id' => $block['properties']['symbolId'],
					'elementId' => $block['id'],
				);

				array_push($symbol_ids, $symbol_id);
			}
			self::add_asset($asset_urls, $key, $block);
		}

		// filter asset_urls using base_url if base_url not included remove item
		$upload_base_path = wp_parse_url($base_url, PHP_URL_PATH);
		$upload_host = wp_parse_url($base_url, PHP_URL_HOST);

		$asset_urls = array_filter(
			$asset_urls,
			function ($asset_item) use ($upload_base_path, $upload_host) {
				$parsed = wp_parse_url($asset_item['url']);

				if (empty($parsed['host']) || $parsed['host'] !== $upload_host) {
					return false;
				}

				if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), array('http', 'https'), true)) {
					return false;
				}

				$path = wp_normalize_path(rawurldecode(isset($parsed['path']) ? $parsed['path'] : ''));

				// Must start with the uploads base path and contain no traversal.
				return $upload_base_path
					&& strpos($path, $upload_base_path) === 0
					&& strpos($path, '..') === false;
			}
		);

		// Fetch top-level symbols used on the page
		$fetched_symbol_ids = array();
		foreach ($symbol_ids as $symbol_id) {
			$symbol_id_val = $symbol_id['id'];
			if (!isset($fetched_symbol_ids[$symbol_id_val])) {
				$fetched_symbol_ids[$symbol_id_val] = true;
				$symbol = Symbol::get_single_symbol($symbol_id_val, true);
				if ($symbol) {
					$symbol['elementId'] = $symbol_id['elementId'];
					$symbols[]           = $symbol;
				}
			}
		}

		// Collect assets from all symbols and fetch any nested symbols
		for ($i = 0; $i < count($symbols); $i++) {
			$current_symbol = $symbols[$i];

			if (empty($current_symbol['symbolData']['data'])) {
				continue;
			}

			foreach ($current_symbol['symbolData']['data'] as $block_key => $block) {
				self::add_asset($asset_urls, $block_key, $block);

				// If a block is a nested symbol that hasn't been fetched yet, fetch and append it
				if ($block['name'] === 'symbol' && !empty($block['properties']['symbolId'])) {
					$nested_symbol_id = $block['properties']['symbolId'];
					if (!isset($fetched_symbol_ids[$nested_symbol_id])) {
						$fetched_symbol_ids[$nested_symbol_id] = true;
						$nested_symbol = Symbol::get_single_symbol($nested_symbol_id, true);
						if ($nested_symbol) {
							$symbols[] = $nested_symbol;
						}
					}
				}
			}
		}

		$data['asset_urls'] = $asset_urls;
		$data['symbols'] = $symbols;

		self::get_assets_make_zip($filename, $asset_urls, $data);
	}

	public static function add_asset(&$asset_urls, $key, $block)
	{
		if ($block['name'] === 'image' && $block['properties']['attributes']['src']) {
			$image_item = array(
				'id' => $key,
				'name' => $block['name'],
				'url' => $block['properties']['attributes']['src'],
			);
			array_push($asset_urls, $image_item);
		}

		if ($block['name'] === 'video' && $block['properties']['attributes']['src']) {
			$video_item = array(
				'id' => $key,
				'name' => $block['name'],
				'url' => $block['properties']['attributes']['src'],
			);
			array_push($asset_urls, $video_item);

			if ($block['properties']['thumbnail']['url']) {
				$video_thumbnail = array(
					'id' => $key,
					'name' => $block['name'],
					'url' => $block['properties']['thumbnail']['url'],
					'thumbnail' => true,
				);
				array_push($asset_urls, $video_thumbnail);
			}
		}

		if ($block['name'] === 'lottie' && $block['properties']['lottie']['src']) {
			$lottie = array(
				'id' => $key,
				'name' => $block['name'],
				'url' => $block['properties']['lottie']['src'],
			);
			array_push($asset_urls, $lottie);
		}
		if ($block['name'] === 'lightbox' && $block['properties']['lightbox']['thumbnail']['src']) {
			$lightbox_thumbnail = array(
				'id' => $key,
				'name' => $block['name'],
				'url' => $block['properties']['lightbox']['thumbnail']['src'],
				'thumbnail' => true,
			);
			array_push($asset_urls, $lightbox_thumbnail);

			$lightbox_media = $block['properties']['lightbox']['media'];

			foreach ($lightbox_media as $key => $media_item) {
				if ($media_item['sources']['original']) {
					$lightbox_media_item = array(
						'id' => $key,
						'name' => $block['name'],
						'url' => $media_item['sources']['original'],
						'index' => $key,
					);
					array_push($asset_urls, $lightbox_media_item);
				}
			}
		}
	}

	public static function get_assets_make_zip($filename, $asset_urls, $data)
	{
		try {
			if (empty($wp_filesystem)) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			global $wp_filesystem;

			$zip = new \ZipArchive();
			$upload_dir = wp_upload_dir();

			$data['asset_urls'] = $asset_urls;

			// Step 1: Name of the zip file to be created
			$zipFileName = $upload_dir['basedir'] . "/$filename.zip";
			$kirki_json_file = $upload_dir['basedir'] . '/kirki-data.json';

			// Step 2: Convert the array to JSON
			$json_data = json_encode($data, JSON_PRETTY_PRINT);

			$is_file_written = $wp_filesystem->put_contents(
				$kirki_json_file,
				$json_data,
				FS_CHMOD_FILE // predefined mode settings for WP files
			);

			if (false === $is_file_written) {
				throw new \Exception('Failed to write kirki-data.json file');
			}

			if (true !== $zip->open($zipFileName, \ZipArchive::CREATE)) {
				throw new \Exception('Failed to create zip file');
			}

			// Step 3: Add file to the zip file
			$uploads_real = realpath($upload_dir['basedir']);
			$uploads_base_path = wp_normalize_path(wp_parse_url($upload_dir['baseurl'], PHP_URL_PATH));
			$allowed_exts = array('jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'mp4', 'mov', 'webm', 'mp3', 'json', 'lottie');

			foreach ($asset_urls as $key => $asset_item) {
				$url = $asset_item['url'];
				$parsed = wp_parse_url($url);

				if (empty($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), array('http', 'https'), true)) {
					continue;
				}

				if (empty($parsed['host']) || $parsed['host'] !== wp_parse_url($upload_dir['baseurl'], PHP_URL_HOST)) {
					continue;
				}

				$path = wp_normalize_path(rawurldecode(isset($parsed['path']) ? $parsed['path'] : ''));
				if (!$uploads_base_path || strpos($path, $uploads_base_path) !== 0) {
					continue; // not under /uploads
				}

				$relative = ltrim(substr($path, strlen($uploads_base_path)), '/');
				if ('' === $relative || false !== strpos($relative, '..')) {
					continue; // empty or traversal
				}

				$file_path = $uploads_real . DIRECTORY_SEPARATOR . $relative;
				$real_path = realpath($file_path);

				// Resolved file must exist, be a regular file, and stay inside uploads.
				if (false === $real_path || !is_file($real_path)) {
					continue;
				}

				if ($uploads_real && strpos($real_path, $uploads_real . DIRECTORY_SEPARATOR) !== 0) {
					continue;
				}

				$ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
				if (!in_array($ext, $allowed_exts, true)) {
					continue; // only media/config files may be exported
				}

				$zip->addFile($real_path, basename($real_path));
			}

			if (false === $zip->addFile($kirki_json_file, 'kirki-data.json')) {
				throw new \Exception('Failed to add kirki-data.json file to zip');
			}

			$zip->close();

			// remove kirki-data.json
			wp_delete_file($kirki_json_file);

			// Step 4: Download the created zip file
			$file_url = add_query_arg(
				array(
					'page-export' => 'true',
					'file-name' => $filename . '.zip',
					'download_file_nonce' => wp_create_nonce('download_file_action'),
				),
				home_url('/')
			);
			wp_send_json($file_url);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage(), 401);
		}
	}

	public static function import()
	{
		set_time_limit(300);

		$is_include_media = HelperFunctions::sanitize_text(isset($_POST['is_include_media']) ? $_POST['is_include_media'] : false);
		$file = $_FILES['file']; // zip file

		self::handle_zip_file_upload($file, $is_include_media);
	}

	public static function download_and_save_zip_file($url, $destination)
	{
		// Execute the cURL session
		$file_content = HelperFunctions::http_get(
			$url,
			array(
				'timeout' => 300, // Seconds
				'redirection' => 0,
			)
		);

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		global $wp_filesystem;

		// Save the file to the destination
		if ($wp_filesystem->put_contents($destination, $file_content, FS_CHMOD_FILE)) {
			return true;
		} else {
			return false;
		}
	}

	public static function template_import()
	{
		$upload_dir = wp_upload_dir();

		$is_include_media = HelperFunctions::sanitize_text(isset($_POST['is_include_media']) ? $_POST['is_include_media'] : false);

		// get template file from url
		$file_url = HelperFunctions::sanitize_text(isset($_POST['file_url']) ? $_POST['file_url'] : false);

		if (!$file_url || !HelperFunctions::is_safe_url($file_url)) {
			wp_send_json_error('Invalid file URL');
		}

		$destination_path = $upload_dir['basedir'] . '/kirki-template.zip';

		if (self::download_and_save_zip_file($file_url, $destination_path)) {
			$file = array(
				'name' => 'kirki-template.zip',
				'tmp_name' => $destination_path,
				'error' => 0,
			);

			self::handle_zip_file_upload($file, $is_include_media);
		} else {
			// Error occurred while downloading or saving the file
			wp_send_json_error('Zip File upload Failed');
		}
	}


	public static function handle_zip_file_upload($file, $is_include_media)
	{
		$upload_dir = wp_upload_dir();

		$file_name = $file['name'];
		$file_tmp = $file['tmp_name'];
		$file_error = $file['error'];

		$file_ext = explode('.', $file_name); // ['file', 'ext']
		$file_ext = strtolower(end($file_ext)); // 'ext'

		$allowed = array('zip');

		if (!in_array($file_ext, $allowed, true)) {
			wp_send_json_error('File type not allowed, please upload zip file');
		}

		if ($file_error !== 0) {
			wp_send_json_error('File upload Failed');
		}

		$file_name_new = uniqid('', true) . '.' . $file_ext; // 'random.ext'
		$file_destination = $upload_dir['basedir'] . '/' . $file_name_new;

		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if (!$wp_filesystem->move($file_tmp, $file_destination)) {
			wp_send_json_error('Something went wrong, please try again');
		}

		$zip = new \ZipArchive();
		$res = $zip->open($file_destination);

		if ($res !== true) {
			wp_send_json_error('Failed to extract zip file');
		}

		$filtered_zip_path = HelperFunctions::filterZipFile($zip, $file_destination);

		if (!$filtered_zip_path) {
			return false;
		}

		$zip->close();
		wp_delete_file($file_destination);

		$temp_folder = 'kirki_temp';
		$temp_folder_path = HelperFunctions::get_temp_folder_path();

		// Reopen the filtered ZIP file for extraction
		$res = $zip->open($filtered_zip_path);

		$zip->extractTo($temp_folder_path);
		$zip->close();

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		global $wp_filesystem;

		$kirki_json_file = $temp_folder_path . '/kirki-data.json';
		$kirki_json_data = $wp_filesystem->get_contents($kirki_json_file);
		$kirki_json_data = json_decode($kirki_json_data, true);

		$blocks = $kirki_json_data['blocks'];
		$symbols = $kirki_json_data['symbols'];

		/**
		 * @deprecated
		 * @see \Kirki\App\Supports\Template::process_template_from_json()
		 */
		if ($is_include_media === 'true') {
			$asset_urls = $kirki_json_data['asset_urls'];

			$pivot_table = array();
			$assets_urls_map = array();

			foreach ($asset_urls as $key => $asset_item) {
				$url = $asset_item['url'];

				if (empty($pivot_table[$url]['url'])) {
					$new_asset = self::upload_file($asset_item);

					if ($new_asset) {
						$pivot_table[$url]['url'] = $new_asset['url'];
						$pivot_table[$url]['attachment_id'] = $new_asset['attachment_id'];

						$asset_urls[$key]['url'] = $new_asset['url'];
						$asset_urls[$key]['attachment_id'] = $new_asset['attachment_id'];
						$asset_urls[$key]['index'] = isset($asset_item['index']) ? $asset_item['index'] : null;
						$asset_urls[$key]['thumbnail'] = isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false;
					}
				} else {
					$asset_urls[$key]['url'] = $pivot_table[$url]['url'];
					$asset_urls[$key]['attachment_id'] = $pivot_table[$url]['attachment_id'];

					$asset_urls[$key]['index'] = isset($asset_item['index']) ? $asset_item['index'] : null;
					$asset_urls[$key]['thumbnail'] = isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false;
				}
			}

			// update blocks with new asset urls
			foreach ($asset_urls as $key => $new_asset) {
				$assets_urls_map[$new_asset['id']] = $new_asset;

				if (isset($blocks[$new_asset['id']]['name'], $new_asset['name']) && $blocks[$new_asset['id']]['name'] === $new_asset['name']) {
					if ($new_asset['name'] === 'image') {
						$blocks[$new_asset['id']]['properties']['attributes']['src'] = $new_asset['url'];
						$blocks[$new_asset['id']]['properties']['wp_attachment_id'] = $new_asset['attachment_id'];
					} elseif ($new_asset['name'] === 'video') {
						if ($new_asset['thumbnail']) {
							$blocks[$new_asset['id']]['properties']['thumbnail']['url'] = $new_asset['url'];
						} else {
							$blocks[$new_asset['id']]['properties']['attributes']['src'] = $new_asset['url'];
						}
					} elseif ($new_asset['name'] === 'lottie') {
						$blocks[$new_asset['id']]['properties']['lottie']['src'] = $new_asset['url'];
					} elseif ($new_asset['name'] === 'lightbox') {
						if ($new_asset['thumbnail']) {
							$blocks[$new_asset['id']]['properties']['lightbox']['thumbnail']['src'] = $new_asset['url'];
						} else {
							$blocks[$new_asset['id']]['properties']['lightbox']['media'][$new_asset['index']]['sources']['original'] = $new_asset['url'];
						}
					}
				}
			}

			// Update symbols with new asset URLs.
			// Fix: property paths must include ['properties'] (e.g. ['properties']['attributes']['src']).
			foreach ($symbols as $sym_key => $symbol) {
				foreach ($symbol['symbolData']['data'] as $key => $block) {
					if (!isset($assets_urls_map[$block['id']])) {
						continue;
					}
					$mapped = $assets_urls_map[$block['id']];
					if ($mapped['name'] === 'image') {
						$symbols[$sym_key]['symbolData']['data'][$key]['properties']['attributes']['src'] = $mapped['url'];
						$symbols[$sym_key]['symbolData']['data'][$key]['properties']['wp_attachment_id'] = $mapped['attachment_id'];
					} elseif ($mapped['name'] === 'video') {
						if ($mapped['thumbnail']) {
							$symbols[$sym_key]['symbolData']['data'][$key]['properties']['thumbnail']['url'] = $mapped['url'];
						} else {
							$symbols[$sym_key]['symbolData']['data'][$key]['properties']['attributes']['src'] = $mapped['url'];
						}
					} elseif ($mapped['name'] === 'lottie') {
						$symbols[$sym_key]['symbolData']['data'][$key]['properties']['lottie']['src'] = $mapped['url'];
					} elseif ($mapped['name'] === 'lightbox') {
						if ($mapped['thumbnail']) {
							$symbols[$sym_key]['symbolData']['data'][$key]['properties']['lightbox']['thumbnail']['src'] = $mapped['url'];
						} else {
							$symbols[$sym_key]['symbolData']['data'][$key]['properties']['lightbox']['media'][$mapped['index']]['sources']['original'] = $mapped['url'];
						}
					}
				}
			}
		}

		// Tracker for saved symbols and post IDs: maps old_id => new_id
		$post_id_tracker       = array();
		$saved_symbols_tracker = array();
		$asset_pivot           = isset($pivot_table) ? $pivot_table : array();

		if (is_array($symbols) && count($symbols) > 0) {
			// Phase 1: Save ALL symbols to the DB first and record old_id => new_id mapping in post_id_tracker.
			foreach ($symbols as &$symbol) {
				$old_symbol_id = $symbol['id'];

				// Remap fields[].default_value asset URLs before saving to DB.
				if (!empty($asset_pivot) && !empty($symbol['symbolData']['fields'])) {
					foreach ($symbol['symbolData']['fields'] as $field_key => $field) {
						if (!in_array($field['type'], array('image', 'video')) || empty($field['default_value'])) {
							continue;
						}
						$default_value = $field['default_value'];
						$old_url       = isset($default_value['url']) ? $default_value['url'] : '';
						if ($old_url && isset($asset_pivot[$old_url])) {
							$default_value['id']  = $asset_pivot[$old_url]['attachment_id'];
							$default_value['url'] = $asset_pivot[$old_url]['url'];
							if (isset($default_value['sources']['original'])) {
								$default_value['sources']['original'] = $asset_pivot[$old_url]['url'];
							}
							$symbol['symbolData']['fields'][$field_key]['default_value'] = $default_value;
						}
					}
				}

				if (!isset($post_id_tracker[$old_symbol_id])) {
					$saved_symbol = Symbol::save_to_db($symbol);
					if ($saved_symbol) {
						$post_id_tracker[$old_symbol_id]       = $saved_symbol['id'];
						$saved_symbols_tracker[$old_symbol_id] = $saved_symbol;
					}
				}
			}
			unset($symbol);

			// Phase 2: Now that all symbols are inserted, update all references using post_id_tracker.
			foreach ($symbols as &$symbol) {
				$old_symbol_id = $symbol['id'];
				if (!isset($post_id_tracker[$old_symbol_id])) {
					continue;
				}
				$new_symbol_id = $post_id_tracker[$old_symbol_id];
				$symbol['id']  = $new_symbol_id;

				$db_symbol_data = get_post_meta($new_symbol_id, 'kirki', true);
				$meta_updated   = false;

				// Update nested symbol references and links in in-memory symbol data
				if (isset($symbol['symbolData']['data']) && is_array($symbol['symbolData']['data'])) {
					foreach ($symbol['symbolData']['data'] as $block_id => &$block_data) {
						if (isset($block_data['properties']['symbolId']) && isset($post_id_tracker[$block_data['properties']['symbolId']])) {
							$block_data['properties']['symbolId'] = $post_id_tracker[$block_data['properties']['symbolId']];
						}
						if (isset($block_data['properties']['attributes']['href']) && isset($post_id_tracker[$block_data['properties']['attributes']['href']])) {
							$block_data['properties']['attributes']['href'] = $post_id_tracker[$block_data['properties']['attributes']['href']];
						}
					}
					unset($block_data);
				}

				// Update nested symbol references and links in DB post meta
				if (isset($db_symbol_data['data']) && is_array($db_symbol_data['data'])) {
					foreach ($db_symbol_data['data'] as $block_id => &$block_data) {
						if (isset($block_data['properties']['symbolId']) && isset($post_id_tracker[$block_data['properties']['symbolId']])) {
							$block_data['properties']['symbolId'] = $post_id_tracker[$block_data['properties']['symbolId']];
							$meta_updated                         = true;
						}
						if (isset($block_data['properties']['attributes']['href']) && isset($post_id_tracker[$block_data['properties']['attributes']['href']])) {
							$block_data['properties']['attributes']['href'] = $post_id_tracker[$block_data['properties']['attributes']['href']];
							$meta_updated                                   = true;
						}
					}
					unset($block_data);
				}

				// Update symbolRootSize in symbol styleBlocks
				if (isset($symbol['symbolData']['styleBlocks']) && is_array($symbol['symbolData']['styleBlocks'])) {
					foreach ($symbol['symbolData']['styleBlocks'] as $style_block_id => &$style_block_data) {
						if (isset($style_block_data['symbolRootSize']['symbolId']) && isset($post_id_tracker[$style_block_data['symbolRootSize']['symbolId']])) {
							$style_block_data['symbolRootSize']['symbolId'] = $post_id_tracker[$style_block_data['symbolRootSize']['symbolId']];
						}
					}
					unset($style_block_data);
				}

				if (isset($db_symbol_data['styleBlocks']) && is_array($db_symbol_data['styleBlocks'])) {
					foreach ($db_symbol_data['styleBlocks'] as $style_block_id => &$style_block_data) {
						if (isset($style_block_data['symbolRootSize']['symbolId']) && isset($post_id_tracker[$style_block_data['symbolRootSize']['symbolId']])) {
							$style_block_data['symbolRootSize']['symbolId'] = $post_id_tracker[$style_block_data['symbolRootSize']['symbolId']];
							$meta_updated                                   = true;
						}
					}
					unset($style_block_data);
				}

				if ($meta_updated) {
					update_post_meta($new_symbol_id, 'kirki', $db_symbol_data);
				}

				unset($symbol['parentSymbolId']);
			}
			unset($symbol);
		}

		// Phase 3: Update page blocks using post_id_tracker and asset_pivot
		if (is_array($blocks)) {
			foreach ($blocks as $block_id => &$block) {
				if (isset($block['properties']['symbolId']) && isset($post_id_tracker[$block['properties']['symbolId']])) {
					$block['properties']['symbolId'] = $post_id_tracker[$block['properties']['symbolId']];
				}
				if (isset($block['properties']['attributes']['href']) && isset($post_id_tracker[$block['properties']['attributes']['href']])) {
					$block['properties']['attributes']['href'] = $post_id_tracker[$block['properties']['attributes']['href']];
				}
				if (isset($block['properties']['symbolElProps']) && is_array($block['properties']['symbolElProps'])) {
					foreach ($block['properties']['symbolElProps'] as $element_prop_id => &$element_prop_data) {
						if (isset($element_prop_data['attributes']['href']) && isset($post_id_tracker[$element_prop_data['attributes']['href']])) {
							$element_prop_data['attributes']['href'] = $post_id_tracker[$element_prop_data['attributes']['href']];
						}
					}
					unset($element_prop_data);
				}
			}
			unset($block);
		}

		// Phase 4: Update page styles with new symbol IDs (symbolRootSize.symbolId)
		if (isset($kirki_json_data['styles']) && is_array($kirki_json_data['styles'])) {
			foreach ($kirki_json_data['styles'] as $style_id => &$style) {
				if (isset($style['symbolRootSize']['symbolId']) && isset($post_id_tracker[$style['symbolRootSize']['symbolId']])) {
					$style['symbolRootSize']['symbolId'] = $post_id_tracker[$style['symbolRootSize']['symbolId']];
				}
			}
			unset($style);
		}

		// Remap componentFieldValues on page blocks (covers per-instance field overrides).
		$blocks = self::remap_component_field_values_in_blocks($blocks, $asset_pivot);

		$kirki_json_data['blocks'] = $blocks;
		$kirki_json_data['symbols'] = $symbols;

		// remove asset_urls
		unset($kirki_json_data['asset_urls']);

		// delete zip file
		$wp_filesystem->delete($file_destination);

		// delete temp folder
		HelperFunctions::delete_directory($temp_folder_path);

		wp_send_json_success($kirki_json_data);
	}

	/**
	 * Process kirki template zip
	 *
	 * @param string  $kirki_template_zip_path
	 * @param boolean $is_include_media
	 * @return boolean|string
	 * 
	 * @deprecated
	 * @see \Kirki\App\Supports\Template::save_kirki_template_from_zip()
	 */
	public static function process_kirki_template_zip($kirki_template_zip_path, $is_include_media = false, $post_id = false)
	{
		$zip = new \ZipArchive();
		$res = $zip->open($kirki_template_zip_path);

		if ($res === true) {
			$temp_folder_path = HelperFunctions::get_temp_folder_path();

			$zip->extractTo($temp_folder_path);
			$zip->close();

			$kirki_json_file = $temp_folder_path . '/kirki-data.json';
			$kirki_json_data = file_get_contents($kirki_json_file);
			$kirki_json_data = json_decode($kirki_json_data, true);

			if ($is_include_media === 'true') {
				$asset_urls = $kirki_json_data['asset_urls'];
				$blocks = $kirki_json_data['blocks'];

				$pivot_table = array();

				foreach ($asset_urls as $key => $asset_item) {
					$url = $asset_item['url'];

					if (empty($pivot_table[$url]['url'])) {
						$new_asset = self::upload_file($asset_item);

						if ($new_asset) {
							$pivot_table[$url]['url'] = $new_asset['url'];
							$pivot_table[$url]['attachment_id'] = $new_asset['attachment_id'];

							$asset_urls[$key]['url'] = $new_asset['url'];
							$asset_urls[$key]['attachment_id'] = $new_asset['attachment_id'];
							$asset_urls[$key]['index'] = isset($asset_item['index']) ? $asset_item['index'] : null;
							$asset_urls[$key]['thumbnail'] = isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false;
						}
					} else {
						$asset_urls[$key]['url'] = $pivot_table[$url]['url'];
						$asset_urls[$key]['attachment_id'] = $pivot_table[$url]['attachment_id'];

						$asset_urls[$key]['index'] = isset($asset_item['index']) ? $asset_item['index'] : null;
						$asset_urls[$key]['thumbnail'] = isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false;
					}
				}

				// update blocks with new asset urls
				foreach ($asset_urls as $key => $new_asset) {
					if (isset($blocks[$new_asset['id']]['name'], $new_asset['name']) && $blocks[$new_asset['id']]['name'] === $new_asset['name']) {
						if ($new_asset['name'] === 'image') {
							$blocks[$new_asset['id']]['properties']['attributes']['src'] = $new_asset['url'];
							$blocks[$new_asset['id']]['properties']['wp_attachment_id'] = $new_asset['attachment_id'];
						} elseif ($new_asset['name'] === 'video') {
							if ($new_asset['thumbnail']) {
								$blocks[$new_asset['id']]['properties']['thumbnail']['url'] = $new_asset['url'];
							} else {
								$blocks[$new_asset['id']]['properties']['attributes']['src'] = $new_asset['url'];
							}
						} elseif ($new_asset['name'] === 'lottie') {
							$blocks[$new_asset['id']]['properties']['lottie']['src'] = $new_asset['url'];
						} elseif ($new_asset['name'] === 'lightbox') {
							if ($new_asset['thumbnail']) {
								$blocks[$new_asset['id']]['properties']['lightbox']['thumbnail']['src'] = $new_asset['url'];
							} else {
								$blocks[$new_asset['id']]['properties']['lightbox']['media'][$new_asset['index']]['sources']['original'] = $new_asset['url'];
							}
						}
					}
				}

				$kirki_json_data['blocks'] = $blocks;
			}

			// remove asset_urls
			unset($kirki_json_data['asset_urls']);

			if ($post_id) {
				$root = array(
					'accept' => '*',
					'children' => array('body'),
					'id' => 'root',
					'name' => 'root',
					'styleIds' => array(),
					'title' => 'Root',
				);
				$kirki_json_data['blocks']['root'] = $root;
				if ($post_id) {
					foreach ($kirki_json_data['styles'] as $key => $style) {
						$style['name'] = HelperFunctions::add_prefix_to_class_name('post-' . $post_id, $style['name']);
						unset($style['isGlobal']);
						unset($style['isDefault']);
						$kirki_json_data['styles'][$key] = $style;
					}
				}
				HelperFunctions::save_kirki_data_to_db($post_id, $kirki_json_data);
			}

			// delete temp folder
			HelperFunctions::delete_directory($temp_folder_path);
			return $kirki_json_data;
		}

		return false;
	}

	/**
	 * @deprecated
	 * @see \Kirki\App\Supports\Template::attach_assets_from_zip()
	 */
	private static function upload_file($asset_item)
	{
		$asset_name = basename($asset_item['url']);
		$temp_folder_path = HelperFunctions::get_temp_folder_path();
		$source_file_path = $temp_folder_path . '/' . $asset_name;
		if (file_exists($source_file_path)) {
			$file_name = basename($source_file_path);

			// Upload the file
			$file_array = array(
				'name' => $file_name,
				'tmp_name' => $source_file_path,
			);

			$_FILES['file'] = $file_array;

			$attachment_id = media_handle_upload(
				'file',
				0,
				array(),
				array(
					'test_form' => false,
					'action' => 'upload-attachment',
				)
			);

			// Check if the upload was successful
			if (!is_wp_error($attachment_id)) {
				$post = get_post($attachment_id);

				$new_asset = array(
					'id' => $asset_item['id'],
					'name' => $asset_item['name'],
					'url' => $post->guid,
					'attachment_id' => $attachment_id,
				);

				return $new_asset;
			}
		}

		return null;
	}


	public static function delete_directory($dirname)
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ($wp_filesystem->exists($dirname)) {
			return $wp_filesystem->delete($dirname, true);
		}

		return false;
	}

	/**
	 * Remap componentFieldValues on page blocks (per-instance field overrides).
	 *
	 * Handles the case where a user has set instance-level overrides on a symbol block.
	 * Media values (image/video) have their URL and attachment id remapped via the
	 * asset pivot built during import. Nested symbol instances are handled recursively.
	 *
	 * @param array $blocks      All page blocks keyed by block ID.
	 * @param array $asset_pivot Map of old_url => ['url' => new_url, 'attachment_id' => new_id].
	 * @return array Updated blocks.
	 */
	private static function remap_component_field_values_in_blocks($blocks, $asset_pivot)
	{
		if (empty($asset_pivot)) {
			return $blocks;
		}

		foreach ($blocks as $block_key => $block) {
			if (empty($block['properties']['componentFieldValues'])) {
				continue;
			}

			$blocks[$block_key]['properties']['componentFieldValues'] = self::remap_component_field_value_entry(
				$block['properties']['componentFieldValues'],
				$asset_pivot
			);
		}

		return $blocks;
	}

	/**
	 * Remap media asset URLs inside a componentFieldValues map.
	 *
	 * Each field value is either a scalar (text, color) or a media object:
	 *   { id, url, sources: { original, thumbnail }, ... }
	 *
	 * @param array $field_values The componentFieldValues map to process.
	 * @param array $asset_pivot  Map of old_url => ['url' => new_url, 'attachment_id' => new_id].
	 * @return array Updated field values.
	 */
	private static function remap_component_field_value_entry($field_values, $asset_pivot)
	{
		if (!is_array($field_values)) {
			return $field_values;
		}

		foreach ($field_values as $field_key => $value) {
			// Only process media values: arrays with a 'url' key.
			if (!is_array($value) || !isset($value['url'])) {
				continue;
			}

			$old_url = $value['url'];
			if (!isset($asset_pivot[$old_url])) {
				continue;
			}

			$value['url'] = $asset_pivot[$old_url]['url'];
			if (array_key_exists('id', $value)) {
				$value['id'] = $asset_pivot[$old_url]['attachment_id'];
			}
			if (isset($value['sources']['original'])) {
				$value['sources']['original'] = $asset_pivot[$old_url]['url'];
			}
			$field_values[$field_key] = $value;
		}

		return $field_values;
	}
}
