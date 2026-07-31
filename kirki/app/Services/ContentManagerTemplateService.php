<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Models\PostMeta;
use Kirki\App\Supports\Template;

use function Kirki\Framework\with_prefix;

/**
 * Initializes Content Manager index/details pages entirely on the backend.
 */
class ContentManagerTemplateService
{
	/** Dedicated index layouts fall back to the shared generic asset. */
	protected const INDEX_TEMPLATES = [
		'recipes' => 'recipe-index.json',
		'team-members' => 'team-member-index.json',
		'projects' => 'project-index.json',
		'portfolio' => 'portfolio-index.json',
		'jobs' => 'job-index.json',
		'default' => 'index.json',
	];

	/** Dedicated details layouts can be added without changing binding logic. */
	protected const DETAILS_TEMPLATES = [
		'jobs' => 'job-details.json',
		'portfolio' => 'portfolio-details.json',
		'projects' => 'project-details.json',
		'recipes' => 'recipe-details.json',
		'team-members' => 'team-member-details.json',
		'default' => 'article-details.json',
	];

	/**
	 * Generate and persist complete Kirki page data.
	 *
	 * @param int    $page_id       Page/template ID being initialized.
	 * @param int    $collection_id Content Manager collection ID.
	 * @param string $page_kind     Either index or details.
	 * @return void
	 * @throws Exception When the collection or page kind is invalid.
	 */
	public function initialize(int $page_id, int $collection_id, string $page_kind)
	{
		if (!in_array($page_kind, ['index', 'details'], true)) {
			throw new Exception(esc_html__('Invalid Content Manager page kind.', 'kirki'));
		}

		$collection = (new CollectionService())->get_single($collection_id);
		if (empty($collection)) {
			throw new Exception(esc_html__('Content Manager collection not found.', 'kirki'));
		}

		$binder = new ContentManagerTemplateBinder();
		$preset_type = $binder->resolve_preset_type($collection);

		if (!$collection->preset_type && $preset_type !== 'generic') {
			$fields = is_array($collection->fields->meta_value ?? null) ? $collection->fields->meta_value : [];
			foreach ($fields as $key => $field) {
				if (empty($field['templateKey'])) {
					$fields[$key]['templateKey'] = $binder->normalize_key($field['title'] ?? '');
				}
			}

			PostMeta::update_meta_value($collection_id, with_prefix('cm_fields'), $fields);
			PostMeta::update_meta_value($collection_id, with_prefix('cm_preset_type'), $preset_type);
			$collection = (new CollectionService())->get_single($collection_id);
		}

		$template = $this->load_template($page_kind, $preset_type);
		$page_data = $binder->bind($template, $collection, [
			'preset_type' => $preset_type,
			'page_kind' => $page_kind,
		]);
		if (!Template::save_template_data($page_id, $page_data)) {
			throw new Exception(esc_html__('Content Manager page template could not be saved.', 'kirki'));
		}
		PostMeta::update_meta_value($page_id, PageMetaKeys::CONTENT_MANAGER_COLLECTION_ID, $collection_id);
		PostMeta::update_meta_value($page_id, PageMetaKeys::CONTENT_MANAGER_PAGE_KIND, $page_kind);
	}

	/** Load a normalized Kirki template asset from public storage. */
	protected function load_template(string $page_kind, string $preset_type)
	{
		$file_name = $page_kind === 'index'
			? (static::INDEX_TEMPLATES[$preset_type] ?? static::INDEX_TEMPLATES['default'])
			: (static::DETAILS_TEMPLATES[$preset_type] ?? static::DETAILS_TEMPLATES['default']);

		$json_file = KIRKI_PUBLIC_ASSETS_URL . '/pre-built-pages/dynamic/' . $file_name;
		$response = wp_remote_get($json_file, ['timeout' => 15]);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			throw new Exception(esc_html__('Content Manager template asset was not found.', 'kirki'));
		}

		$template = json_decode((string) wp_remote_retrieve_body($response), true);
		if (!is_array($template) || empty($template['blocks']) || !isset($template['styles'])) {
			throw new Exception(esc_html__('Content Manager template asset is invalid.', 'kirki'));
		}

		return $template;
	}
}
