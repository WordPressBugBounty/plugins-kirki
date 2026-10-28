<?php

/**
 * This class act like controller for Editor, Iframe, Frontend
 *
 * @package kirki
 */

namespace Kirki;

use Kirki\App\Constants\PageMetaKeys;
use Kirki\App\Services\PageStageMigrationService;
use Kirki\App\Supports\Facades\Page as PageFacade;
use Kirki\HelperFunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Staging handler class.
 *
 * Every method here just calls \Kirki\App\Managers\PageManager. Versions are saved in the
 * kirki_stages / kirki_stage_data tables. The published data stays in wp_postmeta.
 */
class Staging {

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::get_most_recent_stage_version()
	 */
	public static function get_most_recent_stage_version( $post_id, $stage_must = true, $restoring = false, $old_version = false ) {
		return PageFacade::get_most_recent_stage_version( (int) $post_id, $stage_must, $restoring, $old_version );
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::get_all_staged_versions()
	 */
	public static function get_all_staged_versions( $post_id, $internal = false, $withname = false ) {
		$staged_versions = PageFacade::get_all_staged_versions( (int) $post_id )->to_array();

		if ( $internal ) {
			return $staged_versions;
		}

		wp_send_json( $staged_versions );
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::publish_stage_version()
	 */
	public static function publish_stage_version( $internal = false, $post_id = false ) {
		if ( ! $internal ) {
			$post_id = HelperFunctions::sanitize_text( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null );
		}

		if ( ! $post_id ) {
			wp_send_json( false, 400 );
		}

		$staged_versions = PageFacade::publish_stage_version( (int) $post_id )->to_array();

		if ( $internal ) {
			return $staged_versions;
		}

		wp_send_json( $staged_versions );
	}


	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::get_published_stage_version()
	 */
	public static function get_published_stage_version( $post_id ) {
		return PageFacade::get_published_stage_version( (int) $post_id );
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::get_published_staged_version_info()
	 */
	public static function get_published_stage_version_info( $post_id ) {
		$published_version = PageFacade::get_published_staged_version_info( (int) $post_id );

		return $published_version ? $published_version : false;
	}

	public static function get_page_staging_data( $post_id, $version_id ) {
		$kirki_data = PageFacade::read_page_meta( (int) $post_id, PageMetaKeys::BLOCKS, $version_id );

		if ( ! is_array( $kirki_data ) ) {
			$kirki_data           = array();
			$kirki_data['blocks'] = null;
		}

		$styles               = HelperFunctions::get_page_styleblocks( $post_id, $version_id );
		$kirki_data['styles'] = isset( $styles ) ? $styles : '';

		return $kirki_data;
	}

	// This function receives the page data (blocks, styleblocks...) and saves in stage version whichever required in stage
	// returns the data which needs to be saved in Publish version
	/**
	 * @deprecated
	 * @see Kirki\App\Services\PageService::save_page_data()
	 */
	public static function save_page_staging_data_to_db( $post_id, $page_data ) {
		$post_id = (int) $post_id;

		$staging_version = PageFacade::get_most_recent_stage_version( $post_id );
		PageFacade::set_last_edited_datetime_of_stage_version( $post_id );

		if ( isset( $page_data['styles'] ) ) {
			$new_random_styles     = $page_data['styles'];
			$new_global_styles     = array(); // global Styleblocks which won't be saved in publish but stage
			$add_to_publish_global = array(); // global Styleblocks which won't be saved in stage but publish

			foreach ( $new_random_styles as $key => $style ) {
				if ( ( isset( $style['isDefault'] ) && $style['isDefault'] === true ) || ( isset( $style['isGlobal'] ) && $style['isGlobal'] === true ) ) {
					if ( isset( $style['fromStage'] ) && $style['fromStage'] ) {
						$new_global_styles[ $key ] = $style;
					} else {
						// Adding fromStage true because publish version saves only styleblocks having fromStage
						$style['fromStage']            = true;
						$add_to_publish_global[ $key ] = $style;
					}
					unset( $new_random_styles[ $key ] );
				}
			}

			PageFacade::save_style_blocks( $post_id, $new_random_styles, $staging_version );
			PageFacade::save_deprecated_global_style_blocks( $post_id, $new_global_styles, $staging_version );

			if ( count( $add_to_publish_global ) ) {
				$page_data['styles'] = $add_to_publish_global;
			} else {
				unset( $page_data['styles'] ); // Unset if no global style blocks for publish version
			}
		}

		if ( isset( $page_data['usedStyles'] ) ) {
			PageFacade::save_used_global_style_block_ids( $post_id, $page_data['usedStyles'], $staging_version );
			unset( $page_data['usedStyles'] );
		}

		if ( isset( $page_data['usedStyleIdsRandom'] ) ) {
			PageFacade::save_used_style_block_ids( $post_id, $page_data['usedStyleIdsRandom'], $staging_version );
			unset( $page_data['usedStyleIdsRandom'] );
		}

		if ( isset( $page_data['usedFonts'] ) ) {
			PageFacade::save_used_font_list( $post_id, $page_data['usedFonts'], $staging_version );
			unset( $page_data['usedFonts'] );
		}

		if ( isset( $page_data['blocks'] ) ) {
			PageFacade::save_blocks( $post_id, array( 'blocks' => $page_data['blocks'] ), $staging_version );
			unset( $page_data['blocks'] );
		}

		return array(
			'page_data' => $page_data,
			'version'   => $staging_version,
		);
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::restore_stage_version()
	 */
	public static function restore_stage_version() {
		$post_id        = HelperFunctions::sanitize_text( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null );
		$old_version_id = HelperFunctions::sanitize_text( isset( $_POST['stage_version'] ) ? $_POST['stage_version'] : null );

		if ( ! $post_id || ! $old_version_id ) {
			wp_send_json( false, 400 );
		}

		$new_version = PageFacade::restore_stage_version( (int) $post_id, (int) $old_version_id );

		wp_send_json(
			array_merge(
				array( 'versions' => PageFacade::get_all_staged_versions( (int) $post_id )->to_array() ),
				array( 'new_version' => $new_version )
			)
		);
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::rename_stage_version()
	 */
	public static function rename_stage_version() {
		$post_id    = HelperFunctions::sanitize_text( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null );
		$version_id = HelperFunctions::sanitize_text( isset( $_POST['version_id'] ) ? $_POST['version_id'] : null );
		$name       = HelperFunctions::sanitize_text( isset( $_POST['name'] ) ? $_POST['name'] : null );

		if ( ! $post_id || ! $version_id || ! $name ) {
			wp_send_json( false, 400 );
		}

		wp_send_json( PageFacade::rename_stage_version( (int) $post_id, (int) $version_id, $name )->to_array() );
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::remove_stage_version()
	 */
	public static function delete_stage_version() {
		$post_id    = HelperFunctions::sanitize_text( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null );
		$version_id = HelperFunctions::sanitize_text( isset( $_POST['version_id'] ) ? $_POST['version_id'] : null );

		if ( ! $post_id || ! $version_id ) {
			wp_send_json( false, 400 );
		}

		wp_send_json( PageFacade::remove_stage_version( (int) $post_id, (int) $version_id )->to_array() );
	}

	/**
	 * @deprecated Version data is not in wp_postmeta any more, so this key points to nothing.
	 *             Use PageFacade::read_page_meta()/write_page_meta().
	 * @see Kirki\App\Managers\PageManager::get_staged_meta_name()
	 */
	public static function get_staged_meta_name( $meta_name, $post_id, $stage_version = false, $stage_only = false ) {
		return PageFacade::get_staged_meta_name( $meta_name, (int) $post_id, $stage_version, $stage_only );
	}

	/**
	 * Old staged meta keys this page still has in wp_postmeta.
	 *
	 * Empty for pages that are already moved to the new tables. Only pages that are not moved
	 * yet return keys here.
	 */
	public static function get_all_stage_related_meta_names( $post_id ) {
		$migration = new PageStageMigrationService();
		$versions  = $migration->get_legacy_value( (int) $post_id, PageStageMigrationService::LEGACY_VERSIONS_META_KEY );
		if ( ! $versions || ! is_array( $versions ) ) {
			return array();
		}

		$meta_names = array( PageStageMigrationService::LEGACY_VERSIONS_META_KEY );
		foreach ( $versions as $version ) {
			if ( ! is_array( $version ) || ! isset( $version['version'] ) ) {
				continue;
			}
			foreach ( PageMetaKeys::get_stage_content_keys() as $meta_key ) {
				$meta_names[] = $migration->get_legacy_meta_key( (int) $version['version'], $meta_key );
			}
		}
		return $meta_names;
	}

	/**
	 * @deprecated
	 * @see Kirki\App\Managers\PageManager::get_most_recent_unpublished_stage_version()
	 */
	public static function get_most_recent_unpublished_stage_id( $post_id ) {
		$version = PageFacade::get_most_recent_unpublished_stage_version( (int) $post_id );

		return $version ? $version : false;
	}
}
