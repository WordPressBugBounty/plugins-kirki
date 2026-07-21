<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PageContentTypes;
use Kirki\App\Constants\UtilityPageType;
use Kirki\App\DTO\Page\EditorPagePayloadDTO;
use Kirki\App\DTO\Symbol\SymbolDTO;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Services\PageService;
use Kirki\App\Supports\Facades\Symbol;
use Kirki\Framework\Sanitizer;
use Kirki\Framework\Supports\Facades\File;

use function Kirki\App\is_truthy;

class Template
{
	/**
	 * @param string|string[] $name
	 * @param string|null     $prefix
	 * 
	 * @return string|string[]
	 */
	public static function add_prefix_to_class_name($name, $prefix = null)
	{
		
		if (is_array($name)) {
			foreach ($name as $key => $class_name) {
				$name[$key] = static::add_prefix_to_class_name($class_name, $prefix);
			}

			return $name;
		} 

		$name = strtolower($name);

		if (!in_array($name, KIRKI_PRESERVED_CLASS_LIST)) {
			$name = $prefix ? strtolower($prefix) . '-' . $name : $name;
		}

		return $name;
	}

	/**
	 * Assign utility page template
	 * 
	 * @param int     $page_id
	 * @param string  $utility_page_type
	 * 
	 * @return bool
	 */
    public static function assign_utility_page_template(int $page_id, string $utility_page_type) {
		if (in_array($utility_page_type, UtilityPageType::get_constant_values(), true)) {
			$remote_url = KIRKI_PUBLIC_ASSETS_URL . '/pre-built-pages/basic/' . $utility_page_type . '.zip';
			return static::process_template($page_id, $remote_url);
		}

		return false;
	}

	/**
	 * Assign custom page template
	 * 
	 * @param int     $page_id
	 * @param string  $template_url
	 * 
	 * @return bool
	 */
	public static function assign_custom_page_template(int $page_id, string $template_url) {
		return static::process_template($page_id, $template_url);
	}

	/**
	 * Process kirki template zip
	 * 
	 * @param int     $page_id
	 * @param string  $remote_url
	 * 
	 * @return bool
	 */
	protected static function process_template(int $page_id, string $remote_url)
	{
		$file_name_new = uniqid('', true) . '.zip'; // 'random.ext'
		$zip_file_path = FileHandler::download_zip_from_remote($remote_url, $file_name_new);

		if ($zip_file_path) {
			$json_data = static::save_kirki_template_from_zip($page_id, $zip_file_path);

			if (!empty($json_data)) {
				// delete zip file
				File::delete($zip_file_path);
				return true;
			}
		}

		return false;
	}

	/**
	 * Process kirki template zip
	 *
	 * @param int     $page_id
	 * @param string  $zip_path
	 * @return bool|array
	 */
	protected static function save_kirki_template_from_zip(int $page_id, string $zip_path) 
	{
		if ($page_id === 0) {
			return false;
		}

		$kirki_json_data = static::get_kirki_template_from_zip($zip_path);

		$is_include_media = true;
		$should_process_symbols = true;
		$kirki_json_data = static::process_template_from_json(
			$kirki_json_data, 
			!$is_include_media, 
			!$should_process_symbols
		);

		if (!is_truthy($kirki_json_data)) {
			return false;
		}
		
		$kirki_json_data['blocks']['root'] = [
			'accept'   => '*',
			'children' => ['body'],
			'id'       => 'root',
			'name'     => 'root',
			'styleIds' => [],
			'title'    => 'Root',
		];
		
		foreach ($kirki_json_data['styles'] as $key => $style) {
			$style['name'] = static::add_prefix_to_class_name($style['name'], 'post-' . $page_id);
			unset($style['isGlobal']);
			unset($style['isDefault']);
			unset($style['isGlobalStyle']);
			$kirki_json_data['styles'][$key] = $style;
		}

		$payload = EditorPagePayloadDTO::from_array([
			'page' => PageModel::find($page_id),
			'data' => $kirki_json_data
		]);

		foreach (PageContentTypes::get_constant_values() as $page_content_type) {
			(new PageService())->save_page_data($payload, $page_content_type);
		}

		return $kirki_json_data;
	}

	/**
	 * Process kirki template zip
	 *
	 * @param string  $zip_path
	 * @return false|string
	 */
	protected static function get_kirki_template_from_zip(string $zip_path) 
	{
		$temp_folder_path = FileHandler::get_temp_folder_path();

		$result = FileHandler::extract_zip_file($zip_path, $temp_folder_path);

		if ($result === false) {
			File::delete($temp_folder_path);
			return false;
		}

		$kirki_json_file = $temp_folder_path . '/kirki-data.json';

		if (File::missing($kirki_json_file)) {
			File::delete($temp_folder_path);

			return false;
		}

		$kirki_json_data = File::get($kirki_json_file);

		// delete temp folder
		File::delete($temp_folder_path);

		if (empty($kirki_json_data)) {
			return false;
		}

		return $kirki_json_data;
	}

	/**
	 * Process kirki template zip
	 * 
	 * @param string  $kirki_json_data
	 * @param boolean $is_include_media
	 * @param boolean $should_process_symbols
	 * 
	 * @return array
	 */
	protected static function process_template_from_json(string $kirki_json_data, bool $is_include_media = false, bool $should_process_symbols = true)
	{
		$kirki_json_data = json_decode($kirki_json_data, true);

		$asset_urls = $kirki_json_data['asset_urls'] ?? [];
		$blocks     = $kirki_json_data['blocks'] ?? [];
		$symbols 	= $kirki_json_data['symbols'] ?? [];

		if ($is_include_media) {
			$uploaded_attachment_ids = static::attach_assets_from_zip($asset_urls);
			$assets_urls_map = [];

			if (!empty($uploaded_attachment_ids)) {
				$uploaded_attachment_urls = PostModel::query()
					->where_in('ID', array_filter(array_values($uploaded_attachment_ids)))
					->get(['ID', 'guid'])
					->pluck('guid', 'ID')
					->to_array();

				foreach ($asset_urls as $key => $asset_item) {
					$url = $asset_item['url'];
					$assets_urls_map[$asset_item['id']] = $asset_item;

					if (!isset($uploaded_attachment_ids[$url])) {
						$asset_urls[$key] = array_merge($asset_item, [
							'url'           => null,
							'attachment_id' => 0,
							'index'         => isset($asset_item['index']) ? $asset_item['index'] : null,
							'thumbnail'     => isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false,
						]);

						continue;
					}

					$attachment_id = $uploaded_attachment_ids[$url] ?? 0;
					$attachment_url = $uploaded_attachment_urls[$attachment_id] ?? null;

					$asset_urls[$key] = array_merge($asset_item, [
						'url'           => $attachment_url,
						'attachment_id' => $attachment_id,
						'index'         => isset($asset_item['index']) ? $asset_item['index'] : null,
						'thumbnail'     => isset($asset_item['thumbnail']) ? $asset_item['thumbnail'] : false,
					]);
				}
			}

			// update blocks with new asset urls
			foreach ($asset_urls as $key => $asset_item) {
				if (
					!isset($blocks[$asset_item['id']]['name'], $asset_item['name']) 
					|| $blocks[$asset_item['id']]['name'] !== $asset_item['name']
				) {
					continue;
				}

				switch($asset_item['name']) {
					case 'image':
						$blocks[$asset_item['id']]['properties']['attributes']['src'] = $asset_item['url'];
						$blocks[$asset_item['id']]['properties']['wp_attachment_id']  = $asset_item['attachment_id'];

						break;
					case 'video':
						if ($asset_item['thumbnail']) {
							$blocks[$asset_item['id']]['properties']['thumbnail']['url'] = $asset_item['url'];
							break;
						}

						$blocks[$asset_item['id']]['properties']['attributes']['src'] = $asset_item['url'];
						break;
					case 'lottie':
						$blocks[$asset_item['id']]['properties']['lottie']['src'] = $asset_item['url'];
						break;
					case 'lightbox':
						if ($asset_item['thumbnail']) {
							$blocks[ $asset_item['id'] ]['properties']['lightbox']['thumbnail']['src'] = $asset_item['url'];
							break;
						}

						$blocks[$asset_item['id']]['properties']['lightbox']['media'][$asset_item['index']]['sources']['original'] = $asset_item['url'];
						break;
				}
			}

			if ($should_process_symbols) {
				// update symbols with new asset urls
				foreach ($symbols as  $sym_key => $symbol) {
					foreach ($symbol['symbolData']['data'] as $key => $block) {
						$block_id = $block['id'];
	
						if (isset($assets_urls_map[$block_id])) {
							switch($assets_urls_map[$block_id]['name']) {
								case 'image':
									$symbol['symbolData']['data'][$key]['attributes']['src'] = $assets_urls_map[$block_id]['url'];
									$symbol['symbolData']['data'][$key]['wp_attachment_id'] = $assets_urls_map[$block_id]['attachment_id'];
									break;
								case 'video':
									if ($assets_urls_map[$block_id]['thumbnail']) {
										$symbol['symbolData']['data'][$key]['thumbnail']['url'] = $assets_urls_map[$block_id]['url'];
										break;
									}
	
									$symbol['symbolData']['data'][$key]['attributes']['src'] = $assets_urls_map[$block_id]['url'];
									break;
								case 'lottie':
									$symbol['symbolData']['data'][$key]['lottie']['src'] = $assets_urls_map[$block_id]['url'];
									break;
								case 'lightbox':
									if ( $assets_urls_map[$block_id]['thumbnail'] ) {
										$symbol['symbolData']['data'][$key]['lightbox']['thumbnail']['src'] = $assets_urls_map[$block_id]['url'];
										break;
									}
	
									$symbol['symbolData']['data'][$key]['lightbox']['media'][$assets_urls_map[$block_id]['index']]['sources']['original'] = $assets_urls_map[$block_id]['url'];
									break;
	
							}
						}
					}
	
					$symbols[$sym_key]['symbolData']['data'] = $symbol['symbolData']['data'];
				}
			}
		}

		$saved_symbols_tracker = [];

		if ($should_process_symbols && is_truthy($symbols)) {
			foreach ($symbols as $symbol) {
				$symbol_id = $symbol['id'];
				$symbol_element_id = $symbol['elementId'];

				// Check if the symbol is already saved
				if (!isset($saved_symbols_tracker[$symbol_id])) {
					// Save the symbol to the database
					$saved_symbol = Symbol::save(SymbolDTO::from_array($symbol));

					// Add the saved symbol to the tracker
					if ($saved_symbol) {
						$saved_symbols_tracker[$symbol_id] = $saved_symbol->id ?? null;
					}
				}

				// Update all relevant elements with the symbol's ID
				if (
					isset($blocks[$symbol_element_id], $saved_symbols_tracker[$symbol_id]) 
					&& $blocks[$symbol_element_id]['name'] === 'symbol'
				) {
					$blocks[$symbol_element_id]['properties']['symbolId'] = $saved_symbols_tracker[$symbol_id];
				}
			}
		}

		// remove asset_urls
		unset($kirki_json_data['asset_urls']);

		$kirki_json_data['blocks'] = $blocks;
		$kirki_json_data['symbols'] = $symbols;

		return $kirki_json_data;
	}

	protected static function attach_assets_from_zip(array $asset_urls)
	{
		$uploaded_attachment_ids = [];

		foreach ($asset_urls as $asset_item) {
			$url = $asset_item['url'];

			if (isset($uploaded_attachment_ids[$url]) && !empty($uploaded_attachment_ids[$url])) {
				continue;
			}

			$attachment_id = null;
			$asset_name       = basename($asset_item['url']);
			$temp_folder_path = FileHandler::get_temp_folder_path();
			$source_file_path = $temp_folder_path . '/' . Sanitizer::apply_rule($asset_name, Sanitizer::FILE_NAME);

			if (File::exists($source_file_path)) {
				$file_name = basename($source_file_path);

				// Upload the file
				$_FILES['file'] = [
					'name'     => Sanitizer::apply_rule($file_name, Sanitizer::FILE_NAME),
					'tmp_name' => $source_file_path,
				];

				if (!function_exists('media_handle_upload')) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
				}

				$attachment_id = media_handle_upload('file', 0, [], [
						'test_form' => false,
						'action'    => 'upload-attachment',
					]
				);

				// Check if the upload was successful
				if (is_wp_error($attachment_id)) {
					$attachment_id = null;
				}
			}

			$uploaded_attachment_ids[$url] = $attachment_id ? (int) $attachment_id : null;
		}

		return $uploaded_attachment_ids;
	}
}