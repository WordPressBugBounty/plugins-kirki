<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\App\Models\Collection;
use Kirki\App\Supports\ContentManager;

/**
 * Binds collection metadata to an existing normalized Kirki template tree.
 *
 * This class deliberately has no knowledge of element defaults. The template
 * assets own structure and styling; the binder only resolves placeholders,
 * repeats field-backed prototypes and removes unavailable optional slots.
 */
class ContentManagerTemplateBinder
{
	/** @var array */
	protected $fields = [];

	/** @var array */
	protected $fields_by_key = [];

	/** @var array */
	protected $fields_by_id = [];

	/** @var string */
	protected $preset_type = 'generic';

	/** @var string */
	protected $page_kind = '';

	/**
	 * Bind a normalized template to a collection.
	 *
	 * @param array      $template   Template blocks/styles.
	 * @param Collection $collection Collection with fields loaded.
	 * @param array      $context    Preset and page-kind context.
	 * @return array
	 * @throws Exception When a required template binding cannot be resolved.
	 */
	public function bind(array $template, Collection $collection, array $context = [])
	{
		$this->fields = is_array($collection->fields->meta_value ?? null)
			? $collection->fields->meta_value
			: [];
		$this->fields_by_key = [];
		$this->fields_by_id = [];
		foreach ($this->fields as $field) {
			$field_keys = [
				$this->normalize_key((string) ($field['templateKey'] ?? '')),
				$this->normalize_key((string) ($field['title'] ?? '')),
			];
			foreach (array_unique(array_filter($field_keys)) as $field_key) {
				$this->fields_by_key[$field_key] = $field;
			}
			if (!empty($field['id'])) {
				$this->fields_by_id[(string) $field['id']] = $field;
			}
		}
		$this->preset_type = (string) ($context['preset_type'] ?? $this->resolve_preset_type($collection));
		$this->page_kind = in_array($context['page_kind'] ?? '', ['index', 'details'], true)
			? (string) $context['page_kind']
			: '';

		$blocks = $template['blocks'] ?? [];
		$styles = $template['styles'] ?? [];
		$remove_ids = [];

		foreach ($blocks as $id => &$block) {
			if (!isset($block['properties']['dynamicContent'])) {
				continue;
			}
			$dynamic = &$block['properties']['dynamicContent'];
			if (!is_array($dynamic)) {
				continue;
			}

			if ($this->is_collection_binding($block, $dynamic)) {
				$dynamic['type'] = ContentManager::get_child_post_post_type_value($collection->ID);
				continue;
			}

			$value = $dynamic['value'] ?? '';
			if ($value === '__CM_ROLE:index-media__') {
				$field = $this->find_index_media_field($this->preset_type);
				$field ? $this->bind_field($block, $field) : $remove_ids[] = $id;
			} elseif ($value === '__CM_ROLE:details-media__') {
				$field = $this->find_details_media_field($this->preset_type);
				$field ? $this->bind_field($block, $field) : $remove_ids[] = $id;
			} elseif (preg_match('/^__CM_FIELD:([a-z0-9-]+)__$/', $value, $matches)) {
				$field = $this->fields_by_key[$matches[1]] ?? null;
				$field ? $this->bind_field($block, $field) : $remove_ids[] = $id;
			} elseif ($this->is_exported_content_manager_binding($dynamic)) {
				$field = $this->resolve_exported_field($block, $dynamic);
				$field ? $this->bind_field($block, $field) : $remove_ids[] = $id;
			}
		}
		unset($block, $dynamic);

		$this->expand_rich_text_repeat($blocks, $styles);
		foreach (array_unique($remove_ids) as $id) {
			$this->remove_subtree($blocks, $styles, $id);
		}
		$this->prune_empty_wrappers($blocks, $styles);

		$template['blocks'] = $blocks;
		$template['styles'] = $styles;
		$this->assert_resolved($template);

		return $template;
	}

	/**
	 * Determine whether a collection element points at a source collection.
	 *
	 * Local templates use a placeholder; raw Builder exports contain the
	 * source collection's concrete kirki_cm_* post type.
	 */
	protected function is_collection_binding(array $block, array $dynamic)
	{
		if (($dynamic['type'] ?? '') === '__CM_COLLECTION_POST_TYPE__') {
			return true;
		}

		$type = (string) ($dynamic['type'] ?? '');
		return in_array($block['name'] ?? '', ['collection', 'slider'], true)
			&& ($dynamic['collectionType'] ?? '') === 'posts'
			&& strpos($type, KIRKI_CONTENT_MANAGER_PREFIX . '_') === 0;
	}

	/**
	 * Identify a concrete Content Manager binding from an exported page.
	 */
	protected function is_exported_content_manager_binding(array $dynamic)
	{
		$value = (string) ($dynamic['value'] ?? '');

		return ($dynamic['type'] ?? '') === 'post'
			&& $value !== ''
			&& strpos($value, '__CM_') !== 0
			&& strpos($value, 'post_') !== 0;
	}

	/**
	 * Match a source export binding to a field in the target collection.
	 *
	 * Explicit stable keys win. Legacy assets fall back to media roles,
	 * semantic layer titles and, finally, an existing target field ID.
	 */
	protected function resolve_exported_field(array $block, array $dynamic)
	{
		$value = (string) ($dynamic['value'] ?? '');
		$field_key = $this->normalize_key(
			(string) ($block['properties']['customAttributes']['data-kirki-cm-field'] ?? '')
		);

		if ($field_key !== '') {
			return $this->fields_by_key[$field_key] ?? null;
		}

		if (($block['name'] ?? '') === 'image') {
			if ($this->page_kind === 'index') {
				return $this->find_index_media_field($this->preset_type);
			}
			if ($this->page_kind === 'details') {
				return $this->find_details_media_field($this->preset_type);
			}
		}

		$keys = [
			$this->normalize_key((string) ($block['customTitle'] ?? '')),
			$this->normalize_key((string) ($block['title'] ?? '')),
		];
		$contents = $block['properties']['contents'][0] ?? '';
		if (is_string($contents)) {
			$keys[] = $this->normalize_key($contents);
		}

		foreach (array_unique(array_filter($keys)) as $key) {
			if (isset($this->fields_by_key[$key])) {
				return $this->fields_by_key[$key];
			}
		}

		if (isset($this->fields_by_id[$value])) {
			return $this->fields_by_id[$value];
		}

		return null;
	}

	/**
	 * Resolve stored preset metadata or recognize an unmodified legacy preset.
	 */
	public function resolve_preset_type(Collection $collection)
	{
		if ($collection->preset_type && !empty($collection->preset_type->meta_value)) {
			return (string) $collection->preset_type->meta_value;
		}

		$fields = is_array($collection->fields->meta_value ?? null) ? $collection->fields->meta_value : [];
		$field_keys = array_map(function ($field) {
			return $field['templateKey'] ?? $this->normalize_key($field['title'] ?? '');
		}, $fields);
		$signatures = [
			'team-members' => ['email', 'job-title', 'bio', 'phone', 'image', 'website', 'team', 'linkedin', 'facebook'],
			'portfolio' => ['title', 'client-name', 'description', 'image', 'year', 'type', 'link', 'github', 'featured'],
			'projects' => ['title', 'client-name', 'client-logo', 'description', 'image', 'year', 'service', 'link', 'featured'],
			'recipes' => ['title', 'ingredients', 'cooking-instruction', 'image', 'preparation-time', 'thumbnail-image', 'featured'],
			'jobs' => ['title', 'description', 'location', 'salary', 'work-hours', 'job-types', 'requirements', 'opening', 'application-deadline'],
			'clients' => ['client-name', 'product-name', 'email', 'phone', 'purchase-date-product-delivered', 'image', 'product-image-logo'],
			'listings' => ['listing-type', 'rent-or-sale-price', 'description', 'no-of-rooms', 'no-of-baths', 'square-feet', 'availability', 'property-image', 'listing-created-date', 'address', 'agent-contact-info'],
		];

		foreach ($signatures as $preset_type => $required_keys) {
			if (empty(array_diff($required_keys, $field_keys))) {
				return $preset_type;
			}
		}

		return 'generic';
	}

	/** Replace one field placeholder with the collection's persisted field ID. */
	protected function bind_field(array &$block, array $field)
	{
		$block['title'] = $field['title'] ?? $block['title'];
		$field_key = $this->normalize_key(
			(string) ($field['templateKey'] ?? $field['title'] ?? '')
		);
		$dynamic_content = is_array($block['properties']['dynamicContent'] ?? null)
			? $block['properties']['dynamicContent']
			: [];
		$block['properties']['dynamicContent'] = array_merge($dynamic_content, [
			'type' => 'post',
			'value' => $field['id'],
		]);
		if ($field_key !== '') {
			$custom_attributes = is_array($block['properties']['customAttributes'] ?? null)
				? $block['properties']['customAttributes']
				: [];
			$custom_attributes['data-kirki-cm-field'] = $field_key;
			$block['properties']['customAttributes'] = $custom_attributes;
		}
		if (($block['name'] ?? '') === 'image') {
			$block['properties']['attributes']['alt'] = $field['title'] ?? 'Image';
			$block['properties']['attributes']['name'] = $field['title'] ?? 'Image';
		}
	}

	/** Clone the normalized rich-text prototype once per rich-text field. */
	protected function expand_rich_text_repeat(array &$blocks, array &$styles)
	{
		$prototype_id = null;
		foreach ($blocks as $id => $block) {
			if (($block['properties']['dynamicContent']['value'] ?? '') === '__CM_REPEAT:rich-text__') {
				$prototype_id = $id;
				break;
			}
		}
		if ($prototype_id === null) {
			return;
		}

		$prototype = $blocks[$prototype_id];
		$parent_id = $prototype['parentId'];
		$replacement_ids = [];
		$repeat_id_prefix = preg_replace('/Prototype$/', 'Field', $prototype_id);
		$rich_text_fields = array_values(array_filter($this->fields, function ($field) {
			return ($field['type'] ?? '') === CustomFieldTypes::RICH_TEXT;
		}));

		foreach ($rich_text_fields as $index => $field) {
			$block = $prototype;
			$block_id = $repeat_id_prefix . ($index + 1);
			$block['id'] = $block_id;
			$block['title'] = $field['title'] ?? 'Rich Text';
			$block['properties']['contents'] = [$block['title']];
			$this->bind_field($block, $field);
			$block['styleIds'] = $this->clone_styles($prototype['styleIds'] ?? [], $styles, $block_id);
			$blocks[$block_id] = $block;
			$replacement_ids[] = $block_id;
		}

		$children = $blocks[$parent_id]['children'] ?? [];
		$position = array_search($prototype_id, $children, true);
		if ($position !== false) {
			array_splice($children, $position, 1, $replacement_ids);
			$blocks[$parent_id]['children'] = $children;
		}
		$this->remove_subtree($blocks, $styles, $prototype_id, false);
	}

	/** Clone style records so repeated blocks remain independently editable. */
	protected function clone_styles(array $style_ids, array &$styles, string $seed)
	{
		$clones = [];
		foreach ($style_ids as $index => $style_id) {
			if (!isset($styles[$style_id])) {
				continue;
			}
			$new_id = $seed . 'Style' . ($index === 0 ? '' : $index + 1);
			$style = $styles[$style_id];
			$style['id'] = $new_id;
			$style['name'] = strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $new_id));
			$styles[$new_id] = $style;
			$clones[] = $new_id;
		}

		return $clones;
	}

	/** Remove a block, all descendants and their private style records. */
	protected function remove_subtree(array &$blocks, array &$styles, string $id, bool $detach = true)
	{
		if (!isset($blocks[$id])) {
			return;
		}
		foreach ($blocks[$id]['children'] ?? [] as $child_id) {
			$this->remove_subtree($blocks, $styles, $child_id, false);
		}
		if ($detach) {
			$parent_id = $blocks[$id]['parentId'] ?? null;
			if ($parent_id && isset($blocks[$parent_id]['children'])) {
				$blocks[$parent_id]['children'] = array_values(array_diff($blocks[$parent_id]['children'], [$id]));
			}
		}
		foreach ($blocks[$id]['styleIds'] ?? [] as $style_id) {
			$is_used_elsewhere = false;
			foreach ($blocks as $block_id => $block) {
				if ($block_id !== $id && in_array($style_id, $block['styleIds'] ?? [], true)) {
					$is_used_elsewhere = true;
					break;
				}
			}
			if (!$is_used_elsewhere) {
				unset($styles[$style_id]);
			}
		}
		unset($blocks[$id]);
	}

	/** Remove optional div wrappers left empty after unavailable fields. */
	protected function prune_empty_wrappers(array &$blocks, array &$styles)
	{
		do {
			$removed = false;
			foreach ($blocks as $id => $block) {
				if (($block['name'] ?? '') === 'div' && isset($block['children']) && empty($block['children'])) {
					$this->remove_subtree($blocks, $styles, $id);
					$removed = true;
					break;
				}
			}
		} while ($removed);
	}

	protected function find_index_media_field(string $preset_type)
	{
		if ($preset_type === 'recipes') {
			if (isset($this->fields_by_key['image'])) {
				return $this->fields_by_key['image'];
			}
			if (isset($this->fields_by_key['thumbnail-image'])) {
				return $this->fields_by_key['thumbnail-image'];
			}
		}
		return $this->find_field(['image', 'profile-image', 'property-image', 'product-image-logo', 'client-logo'], [CustomFieldTypes::IMAGE]);
	}

	protected function find_details_media_field(string $preset_type)
	{
		if ($preset_type === 'recipes' && isset($this->fields_by_key['image'])) {
			return $this->fields_by_key['image'];
		}
		return $this->find_field(['image', 'property-image', 'product-image-logo', 'client-logo'], [CustomFieldTypes::IMAGE]);
	}

	protected function find_field(array $keys, array $types)
	{
		foreach ($keys as $key) {
			if (isset($this->fields_by_key[$key]) && in_array($this->fields_by_key[$key]['type'] ?? '', $types, true)) {
				return $this->fields_by_key[$key];
			}
		}
		foreach ($this->fields as $field) {
			if (in_array($field['type'] ?? '', $types, true)) {
				return $field;
			}
		}
		return null;
	}

	public function normalize_key(string $value)
	{
		$value = strtolower($value);
		$value = preg_replace('/[^a-z0-9]+/', '-', $value);
		return trim((string) $value, '-');
	}

	/** Fail before persistence when an asset contains an unknown placeholder. */
	protected function assert_resolved(array $template)
	{
		$encoded = wp_json_encode($template);
		if (strpos($encoded, '__CM_') !== false) {
			throw new Exception(esc_html__('Content Manager template contains an unresolved binding.', 'kirki'));
		}

		foreach ($template['blocks'] ?? [] as $block) {
			$dynamic = $block['properties']['dynamicContent'] ?? null;
			if (
				is_array($dynamic)
				&& $this->is_exported_content_manager_binding($dynamic)
				&& !isset($this->fields_by_id[(string) ($dynamic['value'] ?? '')])
			) {
				throw new Exception(esc_html__('Content Manager template contains a foreign field binding.', 'kirki'));
			}
		}
	}
}
