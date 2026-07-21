<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\GlobalDataKeys;
use Kirki\App\Constants\OptionKeys;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Models\PostMeta;
use Kirki\Framework\Supports\Facades\Option;

class GlobalDataManager
{
    /** @var int */
	protected $post_id = 0;

	/** @var array */
	protected $global_style_blocks;

	/** @var array */
	protected $global_ui_controller;

	/** @var array */
	protected $global_ui_saved_data;

	/** @var array */
	protected $global_custom_fonts;

	/**
	 * Get global data post id
	 * 
	 * @return int
	 */
	private function get_post_id()
	{
		if (!empty($this->post_id)) {
			return $this->post_id;
		}

		$is_option_with_prefix = false;
		$post_id = null;
		
		$post_id = Option::get(OptionKeys::GLOBAL_DATA_POST_TYPE_ID, $post_id, $is_option_with_prefix);

		if($post_id) {
			$this->post_id = (int) $post_id;

			return $this->post_id;
		}

		$post_id = Option::get(OptionKeys::DROIP_GLOBAL_DATA_POST_TYPE_ID, $post_id, $is_option_with_prefix);

		if($post_id) {
			$this->post_id = (int) $post_id;

			return $this->post_id;
		}

		//this block will run only once
		$post = PageModel::where('post_type', PostTypes::GLOBAL_DATA)->first();

		$post_id = $post->ID ?? null;
		
		if (empty($post)) {
			$post = PageModel::create_post([
				'post_title' => PostTypes::GLOBAL_DATA,
				'post_type' => PostTypes::GLOBAL_DATA,
				'post_status' => 'draft'
			]);

			$post_id = $post->ID ?? null;
		}

		if($post_id) {
			$autoload = true;
			Option::set(OptionKeys::GLOBAL_DATA_POST_TYPE_ID, $post_id, $autoload, $is_option_with_prefix);
		}

		return $this->post_id = (int) $post_id;
	}

    /**
	 * Get global data using key
	 * 
	 * @param string $key
	 * @return mixed
	 * 
	 */
	public function get(string $key)
	{
		//first get post using KIRKI_GLOBAL_DATA_POST_TYPE_NAME post_type name. if not found then create new one.
		$post_id = $this->get_post_id();

		$meta_value = PostMeta::get_meta_value($post_id, $key, null);

		if ($meta_value !== null) {
			return $meta_value;
		}

		$with_prefix = false;
		$default_value = null;

		// this block will run only once for a legacy option key.
		$value = Option::get($key, $default_value, $with_prefix);

		if (!is_null($value)) {
			PostMeta::update_meta_value($post_id, $key, $value);
			Option::delete($key, $with_prefix);
		}

		return $value;
	}

	/**
	 * Get global data using key
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function update(string $key, $value)
	{
		$post_id = $this->get_post_id();

		PostMeta::update_meta_value($post_id, $key, $value);
	}

	/**
	 * Get deprecated global style blocks
	 * 
	 * @return array
	 */
	public function get_deprecated_global_style_blocks()
	{
		return $this->get(GlobalDataKeys::STYLE_BLOCK_DEPRECATED) ?? [];
	}

	/**
	 * Update global style blocks
	 * 
	 * @param array $styles
	 */
	public function update_style_blocks($styles)
	{
		if (!is_array($styles)) {
			$styles = [];
		}

		$this->update(GlobalDataKeys::STYLE_BLOCKS, $styles);

		$this->global_style_blocks = null;
	}

	/**
	 * Get global style blocks
	 * 
	 * @return array
	 */
	public function get_style_blocks()
	{
		if (!is_null($this->global_style_blocks)) {
			return $this->global_style_blocks;
		}

		return $this->global_style_blocks = $this->get(GlobalDataKeys::STYLE_BLOCKS) ?? [];
	}

	/**
	 * Get global UI controller
	 * 
	 * @return array
	 */
	public function get_global_ui_controller()
	{
		if (!is_null($this->global_ui_controller)) {
			return $this->global_ui_controller;
		}

		return $this->global_ui_controller = $this->get(GlobalDataKeys::UI_CONTROLLER) ?? [];
	}

	/**
	 * Update global UI controller
	 * 
	 * @param array $controller
	 */
	public function update_global_ui_controller($controller)
	{
		if (!is_array($controller)) {
			return;
		}

		$this->update(GlobalDataKeys::UI_CONTROLLER, $controller);

		$this->global_ui_controller = null;
	}

	/**
	 * Get global UI saved data
	 * 
	 * @return array
	 */
	public function get_global_ui_saved_data()
	{
		if (!is_null($this->global_ui_saved_data)) {
			return $this->global_ui_saved_data;
		}

		$saved_data = $this->get(GlobalDataKeys::UI_SAVED_DATA) ?? [];

		$saved_data['variableData'] = $this->get_variable_data($saved_data);

		return $this->global_ui_saved_data = $saved_data;
	}

	/**
	 * Update global UI saved data
	 * 
	 * @param array $fonts
	 */
	public function update_global_ui_saved_data($fonts)
	{
		if (!is_array($fonts)) {
			return;
		}

		$this->update(GlobalDataKeys::UI_SAVED_DATA, $fonts);

		$this->global_ui_saved_data = null;
	}

	/**
	 * Get global custom fonts data
	 * 
	 * @return array
	 */
	public function get_global_custom_fonts()
	{
		if (!is_null($this->global_custom_fonts)) {
			return $this->global_custom_fonts;
		}

		return $this->global_custom_fonts = $this->get(GlobalDataKeys::CUSTOM_FONTS) ?? [];
	}

	/**
	 * Update global custom fonts data
	 * 
	 * @param array $data
	 */
	public function update_global_custom_fonts($data)
	{
		if (!is_array($data)) {
			return;
		}

		$this->update(GlobalDataKeys::CUSTOM_FONTS, $data);

		$this->global_custom_fonts = null;
	}

	/**
	 * Get variable data
	 * 
	 * @param array|null $saved_data, if pass null will get $saved_data otherwise will use $saved_data
	 * 
	 * @return array
	 */
	public function get_variable_data($saved_data = null) {
		$saved_data = is_array($saved_data) ? $saved_data : ($this->get(GlobalDataKeys::UI_SAVED_DATA) ?? []);

		$variables = $saved_data && !empty($saved_data['variableData']['data']) 
			? $this->normalize_variable_data($saved_data['variableData'])
			: $this->initial_variable_data();

		$variables = $this->normalize_old_variable_data($variables);

		$variables = $this->sort_variable_data($variables);

		return $variables;
	}

	/**
	 * Normalize old variable data
	 * 
	 * @param array $variables
	 * 
	 * @return array
	 */
	protected function normalize_old_variable_data($variables) {
		// Check if data needs normalization
		if (!isset($variables['data']) || !is_array($variables['data'])) {
			return $variables;
		}

		// Check if already normalized (has the 4 standard groups with correct keys)
		$expected_keys = ['color', 'size', 'text-style', 'font-family'];
		$actual_keys = array_column($variables['data'], 'key');
		
		if (count($actual_keys) === 4 && empty(array_diff($expected_keys, $actual_keys))) {
			// Already normalized, return as-is
			return $variables;
		}

		// Create fresh groups
		$normalized = [
			'data' => [
				[
					'title'     => 'Colors',
					'key'       => 'color',
					'modes'     => [],
					'variables' => [],
				],
				[
					'title'     => 'Numbers',
					'key'       => 'size',
					'modes'     => [],
					'variables' => [],
				],
				[
					'title'     => 'Text Styles',
					'key'       => 'text-style',
					'modes'     => [],
					'variables' => [],
				],
				[
					'title'     => 'Font Family',
					'key'       => 'font-family',
					'modes'     => [],
					'variables' => [],
				],
			],
		];

		// Index for quick access
		$groups = [];

		foreach ($normalized['data'] as $index => &$group) {
			$groups[$group['key']] = &$normalized['data'][$index];
		}

		unset($group);

		// Track modes per type
		$modes_per_type = [
			'color'       => [],
			'size'        => [],
			'text-style'  => [],
			'font-family' => [],
		];

		// Single pass: process all variables
		foreach ($variables['data'] as $section) {
			if (empty($section['variables'])) {
				continue;
			}

			foreach ($section['variables'] as $variable) {
				// Determine correct group based on variable's type
				$var_type = isset($variable['type']) ? $variable['type'] : null;
				
				if (!$var_type || !isset($groups[$var_type])) {
					continue;
				}

				// Add variable to correct group (move if in wrong section)
				$groups[$var_type]['variables'][] = $variable;

				// Collect modes for this type
				if (isset($variable['value']) && is_array($variable['value'])) {
					foreach (array_keys($variable['value']) as $mode_key) {
						if (!isset($modes_per_type[$var_type][$mode_key])) {
							$modes_per_type[ $var_type ][ $mode_key ] = [
								'key'   => $mode_key,
								'title' => ucfirst($mode_key),
							];
						}
					}
				}
			}
		}

		// Assign collected modes to groups
		foreach ($modes_per_type as $type => $modes) {
			if (isset($groups[$type])) {
				$groups[$type]['modes'] = array_values($modes);

				// Ensure default mode exists
				if (empty($groups[$type]['modes'])) {
					$groups[$type]['modes'][] = [
						'key'   => 'default',
						'title' => 'Default',
					];
				}
			}
		}

		return $normalized;
	}

	/**
	 * Normalize variable data
	 * 
	 * @param array $raw_data
	 * 
	 * @return array
	 * 
	 * @todo: need to refactor this method, copied from \Kirki\Ajax\UserData::normalize_variable_data()
	 */
	public function normalize_variable_data( $raw_data ) {
		if(
			isset($raw_data['data']) 
			&& is_array($raw_data['data']) 
			&& count($raw_data['data']) > 0 
			&& isset($raw_data['data'][0]['key'])
		) {
			return $raw_data; // thats means already normalized.	
		}

		// Start from clean base
		$organized = self::initial_variable_data();

		// Index groups by key for fast access
		$groups = [];

		foreach ($organized['data'] as $index => $group) {
			$groups[$group['key'] ] = &$organized['data'][$index];
		}

		foreach ($raw_data['data'] as $section) {

			/* ---------------------------------
			* Merge modes (unique by key)
			* --------------------------------- */
			if (!empty($section['modes'])) {
				foreach ($groups as &$group) {
					$mode_index = [];

					foreach ($group['modes'] as $existing_mode) {
						if (isset($existing_mode['key'])) {
							$mode_index[$existing_mode['key']] = true;
						}
					}

					foreach ($section['modes'] as $mode) {
						if (isset($mode['key']) && !isset($mode_index[$mode['key']])) {
							$group['modes'][] = $mode;
							$mode_index[$mode['key']] = true;
						}
					}
				}
			}

			/* ---------------------------------
			* Organize variables
			* --------------------------------- */
			if (empty($section['variables'])) {
				continue;
			}

			foreach ($section['variables'] as $variable) {

				if (empty($variable['type'])) {
					continue;
				}

				$group_key = $variable['type'];

				if (!isset($groups[$group_key])) {
					continue;
				}

				$found = false;

				foreach ($groups[$group_key]['variables'] as &$existing) {

					if (isset($existing['id'], $variable['id']) && $existing['id'] === $variable['id']) {

						// Merge values by mode
						$existing['value'] = array_merge(
							$existing['value'] ?? [],
							$variable['value'] ?? []
						);

						$found = true;
						break;
					}
				}

				if (!$found) {
					$groups[$group_key]['variables'][] = $variable;
				}
			}
		}

		return $organized;
	}

	/**
	 * Initial variable data
	 * 
	 * @return array
	 */
	protected function initial_variable_data() {
		return [
			'data' => [
				[
					'title'     => 'Colors',
					'key'       => 'color',
					'modes'     => [
						[
							'title' => 'Default',
							'key'   => 'default',
						],
					],
					'variables' => [],
				],
				[
					'title'     => 'Numbers',
					'key'       => 'size',
					'modes'     => [
						[
							'title' => 'Default',
							'key'   => 'default',
						],
					],
					'variables' => [],
				],
				[
					'title'     => 'Text Styles',
					'key'       => 'text-style',
					'modes'     => [
						[
							'title' => 'Default',
							'key'   => 'default',
						],
					],
					'variables' => [],
				],
				[
					'title'     => 'Font Family',
					'key'       => 'font-family',
					'modes'     => [
						[
							'title' => 'Default',
							'key'   => 'default',
						],
					],
					'variables' => [],
				],
			],

		];
	}

	/**
	 * Sort variable data
	 * 
	 * @param array $variable_data
	 * 
	 * @return array
	 */
	public function sort_variable_data($variable_data) {
		if (empty($variable_data) || !is_array($variable_data)) {
			return $variable_data;
		}

		// Enforce deterministic group order: Color, Number, Text style, Font family
		$order_map = [
			'color'       => 0,
			'size'        => 1,
			'text-style'  => 2,
			'font-family' => 3,
		];

		usort(
			$variable_data['data'],
			function ($first, $second) use ($order_map) {
				$first_order = isset($order_map[$first['key']]) ? $order_map[$first['key']] : 99;
				$second_order = isset($order_map[$second['key']]) ? $order_map[$second['key']] : 99;
				return $first_order - $second_order;
			}
		);

		return $variable_data;
	}

	/**
	 * Get View port list.
	 * this method only for front end. not for editor. cause list data not completed data.
	 *
	 * @return array
	 */
	public function get_view_port_list() {
		$control = $this->get_global_ui_controller();

		if (empty($control) || !isset($control['viewport'], $control['viewport']['list'])) {
			return $this->sort_viewport_list($this->get_initial_viewports()['list']);
		}

		return $this->sort_viewport_list((array) $control['viewport']['list']);
	}

	/**
	 * Sort view port list
	 *
	 * @param array $viewport_list list of view port list.
	 * @return array
	 */
	protected function sort_viewport_list($viewport_list) {
		$viewport_list  = $viewport_list;
		$list = [];
		uasort($viewport_list, static function ($first, $second) {
			return ($first['minWidth'] ?? 0) <=> ($second['minWidth'] ?? 0);
		});
		$passed_md = false;

		foreach ($viewport_list as $key => $value) {
			if ($passed_md) {
				$list[$key] = $value;
				$list[$key]['type'] = 'min';
				$list[$key]['id'] = $key;
			}

			if ($key === 'md') {
				$passed_md = true;
				$list[$key] = $value;
				$list[$key]['id'] = $key;
				$list[$key]['type'] = 'max';
			}
		}
		$passed_md = false;

		foreach ( array_reverse( $viewport_list ) as $key => $value ) {
			if ($passed_md) {
				$list[$key] = $value;
				$list[$key]['type'] = 'max';
				$list[$key]['id'] = $key;
			}

			if ($key === 'md') {
				$passed_md = true;
			}
		}
		return $list;
	}

	/**
	 * Get initial view port list.
	 *
	 * @return array
	 */
	public function get_initial_viewports()
	{
		return [
			'active' => 'md',
			'scale'  => 1,
			'zoom'   => 1,
			'width'  => 1200,
			'mdWidth' => '',
			'defaults' => [
				'md',
				'tablet',
				'mobileLandscape',
				'mobile',
			],
			'list' => [
				'md' => [
					'value'      => 1200,
					'scale'      => 1,
					'minWidth'   => 1200,
					'maxWidth'   => 1200,
					'title'      => 'Desktop',
					'icon'       => 'desktop',
					'activeIcon' => 'desktop-hover',
				],
				'tablet' => [
					'value'      => 991,
					'scale'      => 1,
					'minWidth'   => 991,
					'maxWidth'   => 991,
					'title'      => 'Tablet',
					'icon'       => 'tablet-default',
					'activeIcon' => 'tablet-hover',
				],
				'mobileLandscape' => [
					'value'      => 767,
					'scale'      => 1,
					'minWidth'   => 767,
					'maxWidth'   => 767,
					'title'      => 'Landscape',
					'icon'       => 'phone-hr-default',
					'activeIcon' => 'phone-hr-hover',
				],
				'mobile' => [
					'value'      => 575,
					'scale'      => 1,
					'minWidth'   => 575,
					'maxWidth'   => 575,
					'title'      => 'Mobile',
					'icon'       => 'phone-vr-default',
					'activeIcon' => 'phone-vr-hover',
				],
			],
		];
	}
}