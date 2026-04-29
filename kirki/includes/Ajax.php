<?php

/**
 * All Ajax/API calls will goes here
 *
 * @package kirki
 */

namespace Kirki;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\Ajax\Apps;
use Kirki\Ajax\Collaboration\Collaboration;
use Kirki\Ajax\DynamicContent;
use Kirki\Ajax\Media;
use Kirki\Ajax\Page;
use Kirki\Ajax\PageSettings;
use Kirki\Ajax\Symbol;
use Kirki\Ajax\UserData;
use Kirki\Ajax\Walkthrough;
use Kirki\Ajax\WordpressData;
use Kirki\Ajax\Collection;
use Kirki\Ajax\ExportImport;
use Kirki\Ajax\Comments;
use Kirki\Ajax\WpAdmin;
use Kirki\Ajax\Form;
use Kirki\Ajax\RBAC;
use Kirki\Ajax\Taxonomy;
use Kirki\Ajax\Users;
use Kirki\Ajax\TemplateExportImport;

/**
 * Kirki Ajax handler
 */
class Ajax {


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		 /**
		 * Manage Post API call's from Builder
		 */
		add_action( 'wp_ajax_kirki_get_apis', array( $this, 'kirki_get_apis' ) );
		add_action( 'wp_ajax_kirki_post_apis', array( $this, 'kirki_post_apis' ) );

		add_action( 'wp_ajax_nopriv_kirki_post_apis_nopriv', array( $this, 'kirki_post_apis_nopriv' ) );
		add_action( 'wp_ajax_nopriv_kirki_get_apis', array( $this, 'kirki_get_apis' ) );
		/**
		 * Manage Post API call's from WP Admin
		 */
		add_action( 'wp_ajax_kirki_wp_admin_get_apis', array( $this, 'kirki_wp_admin_get_apis' ) );
		add_action( 'wp_ajax_kirki_wp_admin_post_apis', array( $this, 'kirki_wp_admin_post_apis' ) );

		/**
		 * Manage Post API call's from Frontend (logged in not required)
		 */
	}

	/**
	 * Initialize post api
	 *
	 * @return void
	 */
	public function kirki_post_apis_nopriv() {      //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$endpoint = HelperFunctions::sanitize_text( isset( $_POST['endpoint'] ) ? $_POST['endpoint'] : null );
		if ( HelperFunctions::is_api_call_from_editor_preview() && ! HelperFunctions::is_api_header_post_editor_preview_token_valid() ) {
			wp_send_json_error( 'Not authorized' );
		}
		/**
		 * Single SYMBOL API
		 */
		if ( $endpoint === 'get-single-symbol' ) {
			Symbol::fetch_symbol();
			die();
		}
	}

	/**
	 * Initialize post api
	 *
	 * @return void
	 */
	public function kirki_post_apis() {
		 HelperFunctions::verify_nonce( 'wp_rest' );
		if ( ! is_admin() ) {
			wp_send_json_error( 'Not authorized' );
		}
		/**
		 * PAGE APIS
		 */

		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$endpoint = HelperFunctions::sanitize_text( isset( $_POST['endpoint'] ) ? $_POST['endpoint'] : null );

		if ( HelperFunctions::user_has_post_edit_access() ) {
			if ( 'save-page-data' === $endpoint ) {
				Page::save_page_data();
			}

			if ( $endpoint === 'add-new-page' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::add_new_page();
			}

			if ( $endpoint === 'update-page-data' ) {
				Page::update_page_data();
			}

			if ( $endpoint === 'toggle-disabled-page-symbols' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::toggle_disabled_page_symbols();
			}

			if ( $endpoint === 'remove-unused-style-block-from-db' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::remove_unused_style_block_from_db();
			}

			if ( $endpoint === 'duplicate-page' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::duplicate_page();
			}

			if ( $endpoint === 'delete-page' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::delete_page();
			}

			if ( $endpoint === 'back-to-kirki-editor' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::back_to_kirki_editor();
			}

			if ( $endpoint === 'back-to-wordpress-editor' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Page::back_to_wordpress_editor();
			}

			/**
			 * PAGE SETTINGS
			 */
			if ( 'save-page-settings-data' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				PageSettings::save_page_setting_data();
			}

			/**
			 * PAGE SETTINGS
			 */
			if ( 'save-custom-code-data' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				PageSettings::save_custom_code();
			}

			/**
			 * USER APIS
			 */
			if ( $endpoint === 'save-user-controller' ) {
				UserData::save_user_controller();
			}
			if ( $endpoint === 'save-user-saved-data' ) {
				UserData::save_user_saved_data();
			}
			if ( $endpoint === 'save-user-custom-fonts-data' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				UserData::save_user_custom_fonts_data();
			}

			if ( $endpoint === 'download-google-font-offline' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				UserData::make_google_font_offline();
			}

			if ( $endpoint === 'remove-google-font-offline' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				UserData::remove_google_font_offline();
			}

			/**
			 * SYMBOL SAVE API
			 */
			if ( $endpoint === 'save-user-saved-symbol-data' ) {
				Symbol::save();
			}

			/**
			 * SYMBOL UPDATE API
			 */
			if ( $endpoint === 'update-user-saved-symbol-data' ) {
				Symbol::update();
			}

			/**
			 * SYMBOL DELETE API
			 */
			if ( $endpoint === 'delete-user-saved-symbol-data' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Symbol::delete();
			}

			/**
			 * MEDIA APIS
			 */
			if ( $endpoint === 'upload-media' ) {
				Media::upload_media();
			}

			if ( $endpoint === 'upload-font-zip' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Media::upload_font_zip();
			}

			if ( $endpoint === 'remove-custom-font-folder-from-server' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Media::remove_custom_font_folder_from_server();
			}

			if ( $endpoint === 'upload-base64-img' ) {
				Media::upload_base64_img();
			}

			/**
			 * WALKTHROUGH
			 */
			if ( 'set-walkthrough-shown-state' === $endpoint ) {
				Walkthrough::set_walkthrough_state();
			}

			/**
			 * Collaboration data save
			 */
			if ( 'save-collaboration-actions' === $endpoint ) {
				Collaboration::save_actions();
			}

			/**
			 * Collaboration data save
			 */
			if ( 'install-app' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Apps::install_app();
			}

			if ( 'save-app-settings-using-slug' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Apps::save_app_settings_using_slug();
			}
			if ( 'delete-app-using-slug' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Apps::delete_app_using_slug();
			}
			if ( 'update-app' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Apps::update_app();
			}

			/**
			 * Export page data
			 */
			if ( 'import-page-data' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				ExportImport::import();
			}
			/**
			 * Export template data
			 */
			if ( 'import-template-data' === $endpoint && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				ExportImport::template_import();

			}

			/**
			 * Export page dat
			 */
			if ( 'export-page-data' === $endpoint ) {
				ExportImport::export();
			}
			if ( $endpoint === 'import-template-using-url' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				TemplateExportImport::import_using_url();
			}
			if ( $endpoint === 'process-imported-template' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				TemplateExportImport::processImport();
			}
			if ( $endpoint === 'check-existing-template-data' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				TemplateExportImport::check_existing_template_data();
			}

			if ( $endpoint === 'rename-staging-version' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Staging::rename_stage_version();
			}

			if ( $endpoint === 'delete-staging-version' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Staging::delete_stage_version();
			}

			if ( $endpoint === 'publish-staging-version' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Staging::publish_stage_version();
			}

			if ( $endpoint === 'restore-staging-version' && HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				Staging::restore_stage_version();
			}
		}

		if ( $endpoint === 'get-single-symbol' ) {
			Symbol::fetch_symbol();
		}
	}

	/**
	 * Initialize the get apis
	 *
	 * @return void
	 */
	public function kirki_get_apis() {
		if ( HelperFunctions::is_api_call_from_editor_preview() && ! HelperFunctions::is_api_header_post_editor_preview_token_valid() ) {
			wp_send_json_error( 'Not authorized' );
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$endpoint = HelperFunctions::sanitize_text( isset( $_GET['endpoint'] ) ? $_GET['endpoint'] : null );
		if ( 'collect-collaboration-actions' !== $endpoint && 'delete-collaboration-connection' !== $endpoint ) {
			// TODO: Need to verify for collaboration.
			HelperFunctions::verify_nonce( 'wp_rest' );
		}

		if ( ! is_admin() ) {
			wp_send_json_error( 'Not authorized' );
		}
		/**
		 * PAGE APIS
		 */
		if ( $endpoint === 'get-page-data' ) {
			Page::get_page_blocks_and_styles();
		}

		if ( $endpoint === 'get-wp-single-post' ) {
			$post_id = (int) HelperFunctions::sanitize_text( isset( $_GET['post_id'] ) ? $_GET['post_id'] : null );
			$post    = get_post( $post_id );

			if ( ! $post ) {
				wp_send_json_error( 'Post not found' );
			}

			wp_send_json_success( $post );
		}

		if ( $endpoint === 'get-pages-list' ) {
			Page::fetch_list_api();
		}

		if ( $endpoint === 'get-pages-for-pages-panel' ) {
			Page::get_pages_for_pages_panel();
		}
		if ( $endpoint === 'get-data-list-for-template-edit-search-flyout' ) {
			Page::get_data_list_for_template_edit_search_flyout();
		}
		if ( $endpoint === 'get-posts-list' ) {
			Page::fetch_post_list_data_post_type_wise();
		}

		if ( $endpoint === 'get-current-page-data' ) {
			Page::get_current_page_data();
		}
		if ( $endpoint === 'get-unused-class-info-from-db' ) {
			Page::get_unused_class_info_from_db();
		}
		if ( $endpoint === 'validate-wp-post-slug' ) {
			Page::validate_wp_post_slug();
		}

		if ( $endpoint === 'get-page-html' ) {
			Page::get_page_html();
		}

		/**
		 * USER DATA APIS
		 */
		if ( $endpoint === 'get-user-controller' ) {
			UserData::get_user_controller();
		}

		if ( $endpoint === 'is-user-logged-in' ) {
			UserData::check_user_login();
		}

		/**
		 * USER DATA APIS
		 */
		if ( $endpoint === 'get-user-saved-data' ) {
			UserData::get_user_saved_data();
		}

		/**
		 * USER DATA APIS
		 */
		if ( $endpoint === 'get-app-list' ) {
			Apps::get_app_list();
		}
		/**
		 * USER DATA APIS
		 */
		if ( $endpoint === 'get-installed-app-list' ) {
			Apps::get_installed_apps_list();
		}

		/**
		 * USER DATA APIS
		 */
		if ( $endpoint === 'get-app-settings-using-slug' ) {
			Apps::get_app_settings_using_slug();
		}

		if ( $endpoint === 'get-user-custom-fonts-data' ) {
			UserData::get_user_custom_fonts_data();
		}

		/**
		 * GET SYMBOL LIST API
		 */
		if ( $endpoint === 'get-symbol-list' ) {
			Symbol::fetch_list( false, true );
		}

        if ($endpoint === 'get-page-custom-section') {
            $type = HelperFunctions::sanitize_text(isset($_GET['type']) ? $_GET['type'] : '');
            wp_send_json(HelperFunctions::get_page_custom_section($type, true));
        }

		/**
		 * GET Single prebuilt html API
		 */
		if ( $endpoint === 'get-pre-built-html' ) {
			Symbol::get_pre_built_html_using_url();
		}

		/**
		 * GET DYNAMIC CONTENT API
		 */
		if ( $endpoint === 'get-dynamic-content' ) {
			DynamicContent::get_dynamic_element_data();
		}

		if ( $endpoint === 'get-post-terms' ) {
			Taxonomy::get_post_terms();
		}

		if ( $endpoint === 'get-terms' ) {
			Taxonomy::get_terms();
		}

		if ( $endpoint === 'get-post-type-taxonomies' ) {
			Taxonomy::get_post_type_taxonomies();
		}

		if ( $endpoint === 'get-all-terms-by-post-type' ) {
			Taxonomy::get_all_terms_by_post_type();
		}

		if ( $endpoint === 'get_visibility_condition_fields' ) {
			DynamicContent::get_visibility_condition_fields();
		}

		if ( $endpoint === 'get_dynamic_content_fields' ) {
			DynamicContent::get_dynamic_content_fields();
		}

		/**
		 * GET WordPress MENUS API
		 */
		if ( $endpoint === 'get-wp-menus' ) {
			WordpressData::get_wordpress_menus_data();
		}

		/**
		 * GET WordPress POST TYPES API
		 */
		if ( $endpoint === 'get-wp-post-types' ) {
			WordpressData::get_wordpress_post_types_data();
		}

		/**
		 * GET WordPress POST TYPES API
		 */
		if ( $endpoint === 'get-wp-comment-types' ) {
			WordpressData::get_wordpress_comment_types_data();
		}

		/**
		 * GET WordPress SINGLE MENU DATA API
		 */
		if ( $endpoint === 'get-wp-sigle-menu' ) {
			//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$term_id = HelperFunctions::sanitize_text( isset( $_GET['term_id'] ) ? $_GET['term_id'] : null );
			WordpressData::get_wordpress_single_menu_data( $term_id );
		}

		/**
		 * PAGE SETTINGS
		 */
		if ( $endpoint === 'get-page-settings-data' ) {
			PageSettings::get_page_settings_data();
		}

		if ( $endpoint === 'get-custom-code' ) {
			PageSettings::get_custom_code();
		}

		/**
		 * WALKTHROUGH
		 */
		if ( 'get-walkthrough-shown-state' === $endpoint ) {
			Walkthrough::get_walkthrough_state();
		}

		/**
		 * COLLECTION
		 */
		if ( 'get-collection' === $endpoint ) {
			Collection::get_collection();
		}

		if ( 'get-external-collection-options' === $endpoint ) {
			Collection::get_external_collection_options();
		}

		if ( 'get-external-collection-item-type' === $endpoint ) {
			Collection::get_external_collection_item_type();
		}

		/**
		 * GET USERS
		 */

		if ( 'get-users-of-collection' === $endpoint ) {
			Users::get_users_of_collection();
		}

		/**
		 * COMMENTS
		 */
		if ( 'get-comments' === $endpoint ) {
			Comments::get_comments();
		}

		/**
		 * AUTHOR LIST
		 */
		if ( 'get-authors' === $endpoint ) {
			WordpressData::get_author_list();
		}

		/**
		 * ROLE LIST
		 */

		if ( 'get-roles' === $endpoint ) {
			WordpressData::get_role_list();
		}

		/**
		 * USER LIST
		 */
		if (
			'get-users' === $endpoint
		) {
			WordpressData::get_user_list();
		}

		/**
		 * CATEGORY LIST
		 */
		if ( 'get-categories' === $endpoint ) {
			WordpressData::get_category_list();
		}

		/**
		 * GET ACCESS LEVEL
		 */
		if ( 'editor-access-level' === $endpoint ) {
			RBAC::get_editor_access_level();
		}

		if ( $endpoint === 'get-common-data' ) {
			WpAdmin::get_common_data();
		}

		/**
		 * Collaboration data get
		 */
		if ( 'collect-collaboration-actions' === $endpoint ) {
			Collaboration::send_actions();
		}
		/**
		 * Collaboration data get
		 */
		if ( 'delete-collaboration-connection' === $endpoint ) {
			$session_id = HelperFunctions::sanitize_text( $_GET['session_id'] );
			Collaboration::delete_connection( $session_id );
		}

		if ( $endpoint === 'get-connected-collaboration-users-list' ) {
			$post_id = HelperFunctions::sanitize_text( $_GET['post_id'] );
			$res     = Collaboration::get_connected_collaboration_users_list( $post_id );
			wp_send_json( $res );
		}

		/**
		 * Staging GET APIs
		 */

		if ( 'get-all-staged-versions' === $endpoint ) {
			$post_id = (int) HelperFunctions::sanitize_text( isset( $_GET['post_id'] ) ? $_GET['post_id'] : null );
			Staging::get_all_staged_versions( $post_id, false, true );
		}
	}

	/**
	 * Initialize the admin post apis
	 *
	 * @return void
	 */
	public function kirki_wp_admin_post_apis() {
		if ( ! HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
			wp_send_json_error( 'Not authorized' );
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$endpoint = HelperFunctions::sanitize_text( isset( $_POST['endpoint'] ) ? $_POST['endpoint'] : null );

		if ( $endpoint === 'save-common-data' ) {
			WpAdmin::save_common_data();
		}

		if ( $endpoint === 'update-license-validity' ) {
			WpAdmin::update_license_validity();
		}

		if ( $endpoint === 'update-access-level' ) {
			RBAC::update_access_level();
		}

		if ( $endpoint === 'delete-form-row' ) {
			Form::delete_form_row();
		}

		if ( $endpoint === 'delete-form' ) {
			Form::delete_form();
		}

		if ( $endpoint === 'update-form-cell' ) {
			Form::update_form_row();
		}

		/**
		 * Export Template
		 */
		if ( $endpoint === 'import-template' ) {
			TemplateExportImport::import();
		}

		if ( $endpoint === 'process-imported-template' ) {
			TemplateExportImport::processImport();
		}

		if ( $endpoint === 'process-export-template' ) {
			TemplateExportImport::processExport();
		}

		if ( $endpoint === 'save-editor-read-only-access-data' ) {
			Page::save_editor_read_only_access_data();
		}
		/**
		 * Export Template
		 */

		if ( $endpoint === 'export-template' ) {
			TemplateExportImport::export();
		}

	}

	/**
	 * Initialize the admin get apis
	 *
	 * @return void
	 */
	public function kirki_wp_admin_get_apis() {
		if ( ! is_admin() ) {
			wp_send_json_error( 'Not authorized' );
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$endpoint = HelperFunctions::sanitize_text( isset( $_GET['endpoint'] ) ? $_GET['endpoint'] : null );

		if ( $endpoint === 'get-common-data' ) {
			WpAdmin::get_common_data();
		}

		// From manipulation from admin dashboard.
		if ( $endpoint === 'get-forms' ) {
			Form::get_forms();
		}

		if ( $endpoint === 'get-form-data' ) {
			Form::get_form_data();
		}

		if ( $endpoint === 'get-wp-admin-page-data' ) {
			Page::get_pages_for_pages_panel();
		}

		if ( $endpoint === 'get-members-based-on-role' ) {
			RBAC::members_based_on_role();
		}

		if ( $endpoint === 'download-form-data' ) {
			if ( ! HelperFunctions::has_access( KIRKI_ACCESS_LEVELS['FULL_ACCESS'] ) ) {
				wp_send_json_error( 'Not authorized' );
			}

			Form::download_form_data();
		}

		// From manipulation from admin dashboard.

		if ( $endpoint === 'get-editor-read-only-access-data' ) {
			Page::get_editor_read_only_access_data();
		}
	}
}
