<?php

/**
 * Register routes for Media and Frontend
 *
 * @package kirki
 */

namespace Kirki;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\API\ContentManager\ContentManagerRest;
use Kirki\API\KirkiComments\KirkiCommentsRest;
use Kirki\API\Media;
use Kirki\API\Frontend\FrontendApi;

/**
 * API Class
 */
class API {



	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {
		 add_action( 'rest_api_init', array( $this, 'register_api' ) );

		if ( isset( $_GET['page-export'], $_GET['file-name'] ) && $_GET['page-export'] === 'true' ) {
			// TODO: need to check nonce
			$this->downloadZIP();
		}
	}

	/**
	 * Register_api
	 *
	 * @return void
	 */
	public function register_api() {
		// Media apis.
		$media = new Media();
		$media->register_routes();

		$content_manager = new ContentManagerRest();
		$content_manager->register_routes();

		$kirki_comments = new KirkiCommentsRest();
		$kirki_comments->register_routes();

		FrontendApi::register();
	}

	private function downloadZIP() {
		$upload_dir = wp_upload_dir();
		$file_name  = HelperFunctions::sanitize_text( $_GET['file-name'] );
		$file_name  = basename( $file_name );
		// Check if the file has a .zip extension
		if ( ! pathinfo( $file_name, PATHINFO_EXTENSION ) === 'zip' ) {
			echo 'Invalid file type.';
			die();
		}
		$zipFilePath = $upload_dir['basedir'] . "/$file_name";
		// Send the zip file to the client.
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . filesize( $zipFilePath ) );
		$this->output_file_and_cleanup( $zipFilePath, $file_name );
		exit;
	}

	private function output_file_and_cleanup( $path, $name ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->exists( $path ) ) {
			echo $wp_filesystem->get_contents( $path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_delete_file( $path );
		}
	}
}
