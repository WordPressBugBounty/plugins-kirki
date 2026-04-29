<?php

/**
 * Media api controller
 *
 * @package kirki
 */

namespace Kirki\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;
use enshrined\svgSanitize\Sanitizer;


/**
 * Media API Class
 */
class Media {


	/**
	 * Format media data
	 *
	 * @param object $post wp post.
	 * @return object formatted_data.
	 */
	public function format_media_data( $post ) {
		$media_categories = array(
			'image'  => KIRKI_SUPPORTED_MEDIA_TYPES['image'],
			'video'  => KIRKI_SUPPORTED_MEDIA_TYPES['video'],
			'svg'    => KIRKI_SUPPORTED_MEDIA_TYPES['svg'],
			'audio'  => KIRKI_SUPPORTED_MEDIA_TYPES['audio'],
			'lottie' => KIRKI_SUPPORTED_MEDIA_TYPES['lottie'],
			'pdf'    => KIRKI_SUPPORTED_MEDIA_TYPES['pdf'],
			'json'    => KIRKI_SUPPORTED_MEDIA_TYPES['json'],
		);

		$formatted_data = array();

		foreach ( $post as $key => $value ) {
			if ( 'ID' === $key ) {
				$formatted_data['id'] = $value;
			} elseif ( 'post_mime_type' === $key ) {
				foreach ( $media_categories as $category => $mime_types ) {
					if ( in_array( $value, $mime_types, true ) ) {
						$formatted_data['category'] = 'svg' === $category ? 'image' : $category;
						$formatted_data['type']     = $value;
					}
				}
			} elseif ( 'guid' === $key ) {
				$formatted_data['url'] = $value;
			} elseif ( 'post_name' === $key ) {
				$formatted_data['name'] = $value;
				$formatted_data['alt']  = $value;
			}
		}

		// media file size converting to human readable format
		$formatted_data['file_size'] = filesize( get_attached_file( $post->ID ) );
		$formatted_data['file_size'] = size_format( $formatted_data['file_size'] );

		// media file extension
		$file_path                        = get_attached_file( $post->ID );
		$formatted_data['file_extension'] = pathinfo( $file_path, PATHINFO_EXTENSION );

		$formatted_data['trash'] = false;

		$formatted_data['thumbnail'] = wp_get_attachment_image_url( $post->ID );

		return $formatted_data;
	}

	/**
	 * Upload Media api
	 *
	 * @return void wp_send_json
	 */
	public static function upload_media() {
		 $data = array();
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$files = isset( $_FILES['files'] ) ? wp_unslash( $_FILES['files'] ) : null;
		if ( ! $files ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Invalid files',
				)
			);
			die();
		}
		foreach ( $files['name'] as $key => $value ) {
			if ( isset( $files['name'][ $key ] ) ) {
				$tmp_name = wp_unslash( $files['tmp_name'][ $key ] );
				$type     = wp_unslash( $files['type'][ $key ] );
				if ( 'image/svg+xml' === $type && ! ( new self() )->validate_svg( $tmp_name ) ) {
					wp_send_json(
						array(
							'status'  => 'fail',
							'message' => 'Invalid SVG file',
						)
					);
					die();
				}
				$file = array(
					'name'     => wp_unslash( $files['name'][ $key ] ),
					'type'     => $type,
					'tmp_name' => $tmp_name,
					'error'    => wp_unslash( $files['error'][ $key ] ),
					'size'     => wp_unslash( $files['size'][ $key ] ),
				);

				$attachment_id = self::upload_single_media( $file );

				if ( is_wp_error( $attachment_id ) ) {
					$message = 'Some error occurred, please try again';
					if ( isset( $attachment_id->errors['upload_error'][0] ) ) {
						$message = $attachment_id->errors['upload_error'][0];
					}
					wp_send_json(
						array(
							'status'  => 'fail',
							'message' => $message,
						)
					);
				} else {
					$post           = get_post( $attachment_id );
					$formatted_data = ( new self() )->format_media_data( $post );
					$data[]         = $formatted_data;
				}
			}
		}

		wp_send_json(
			array(
				'status' => 'success',
				'data'   => $data,
			)
		);
	}

	public static function upload_single_media( $file ) {
		$file = self::kirki_handle_upload_prefilter( $file );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$_FILES = array( 'upload_file' => $file );

		// $attachment_id = media_handle_upload('upload_file', 0);
		$attachment_id = media_handle_upload(
			'upload_file',
			0,
			array(),
			array(
				'test_form' => false,
				'action'    => 'upload-attachment',
			)
		);

		return $attachment_id;
	}


	/**
	 * Upload Media Pre filter.
	 * This method will call from 'wp_handle_upload_prefilter'this hook. which is imeplemented inside plugin init events file.
	 *
	 * @param array $file Original file.
	 * @return array $file Converted file. If image optimization is enabled from kirki dashboard menu. then any image related file will converted to webp and return as file.
	 */
	public static function kirki_handle_upload_prefilter( $file ) {
		$common_data = WpAdmin::get_common_data( true );
		if ( ! isset( $common_data['image_optimization'] ) || ! $common_data['image_optimization'] ) {
			return $file;
		}
		$filetype = wp_check_filetype( $file['name'] );

		if ( 'image/jpeg' === $filetype['type'] || 'image/png' === $filetype['type'] ) {
			if ( ! extension_loaded( 'gd' ) ) {
				return $file;
			}

			$image           = ( 'image/jpeg' === $filetype['type'] ) ? imagecreatefromjpeg( $file['tmp_name'] ) : imagecreatefrompng( $file['tmp_name'] );
			$webp_image_path = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $file['tmp_name'] ) . '.webp';

			imagewebp( $image, $webp_image_path, 80 );
			imagedestroy( $image );

			// copy the webp image back to the original tmp location.
			copy( $webp_image_path, $file['tmp_name'] );

			$file['name'] = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $file['name'] ) . '.webp';
			$file['type'] = 'image/webp';
		}

		return $file;
	}

	/**
	 * Convert images generated sizes to webp format.
	 * This method will call from 'wp_generate_attachment_metadata' this filter hook. which is imeplemented inside plugin init events file.
	 *
	 * @param array $metadata uploaded files attachment meta data.
	 * @return array $metadata If image optimization is enabled from kirki dashboard menu then it will convert all images to webp otherwise return default metadata.
	 */
	public static function kirki_convert_sizes_to_webp( $metadata ) {
		$common_data = WpAdmin::get_common_data( true );
		if (
			! isset( $common_data['image_optimization'] ) ||
			! $common_data['image_optimization'] ||
			! isset( $metadata['sizes'] ) ||
			! isset( $metadata['image_meta'] )
		) {
			return $metadata;
		}
		if ( ! isset( $metadata['file'] ) ) {
			return $metadata;
		}
		$upload_dir         = wp_upload_dir();
		$original_image_dir = trailingslashit( $upload_dir['basedir'] ) . dirname( $metadata['file'] );
		$image_files        = array_merge( array( $metadata['file'] ), array_column( $metadata['sizes'], 'file' ) );

		foreach ( $image_files as $image_file ) {
			$original_image_path = trailingslashit( $original_image_dir ) . $image_file;
			$webp_image_path     = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $original_image_path ) . '.webp';

			if ( ! file_exists( $webp_image_path ) ) {
				if ( ! extension_loaded( 'gd' ) ) {
					continue;
				}

				$image_info = getimagesize( $original_image_path );

				switch ( $image_info[2] ) {
					case IMAGETYPE_JPEG:
						$image = imagecreatefromjpeg( $original_image_path );
						break;
					case IMAGETYPE_PNG:
						$image = imagecreatefrompng( $original_image_path );
						break;
					case IMAGETYPE_GIF:
						$image = imagecreatefromgif( $original_image_path );
						break;
					default:
						return $metadata;
				}

				imagewebp( $image, $webp_image_path, 80 );
				imagedestroy( $image );
			}

			// Delete the original image.
			if ( file_exists( $original_image_path ) ) {
				wp_delete_file( $original_image_path );
			}

			$metadata = self::kirki_replace_file_in_metadata( $metadata, $image_file, basename( $webp_image_path ) );
		}

		return $metadata;
	}

	/**
	 * Replace file to meta data.
	 *
	 * @param array  $metadata files attachment metadata.
	 * @param string $old_file old file original path. (jpg, png, gif).
	 * @param string $new_file new file original path.
	 *
	 * @return array $metadata files updated attachment metadata.
	 */
	private static function kirki_replace_file_in_metadata( $metadata, $old_file, $new_file ) {
		if ( $old_file === $metadata['file'] ) {
			$metadata['file'] = str_replace( $old_file, $new_file, $metadata['file'] );
		}

		foreach ( $metadata['sizes'] as $size => $info ) {
			if ( $old_file === $info['file'] ) {
				$metadata['sizes'][ $size ]['file'] = $new_file;
			}
		}

		return $metadata;
	}

	/**
	 * Upload font zip
	 *
	 * @return void wp_send_json.
	 */
	public static function upload_font_zip() {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$file            = isset( $_FILES['file'] ) ? wp_unslash( $_FILES['file'] ) : null;
		$uploads         = wp_upload_dir();
		$multiple_files  = self::normalize_multiple_files( isset( $_FILES['files'] ) ? $_FILES['files'] : null );

		if ( ! empty( $multiple_files ) ) {
			self::handle_multiple_raw_font_upload( $multiple_files, $wp_filesystem, $uploads );
			return;
		}

		if ( ! $file || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Invalid file',
				),
			);
		}

		$filename           = sanitize_file_name( $file['name'] );
		$file_extension     = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$allowed_font_files = array( 'ttf', 'otf', 'woff', 'woff2' );

		if ( 'zip' === $file_extension ) {
			self::handle_font_zip_upload( $file, $filename, $wp_filesystem, $uploads );
			return;
		}

		if ( in_array( $file_extension, $allowed_font_files, true ) ) {
			self::handle_raw_font_upload( $file, $filename, $file_extension, $wp_filesystem, $uploads );
			return;
		}

		wp_send_json(
			array(
				'status'  => 'failure',
				'message' => 'Only .zip, .ttf, .otf, .woff, .woff2 files are allowed',
			)
		);
	}

	private static function handle_raw_font_upload( $file, $filename, $file_extension, $wp_filesystem, $uploads, $family_override = null ) {
		$allowed_font_files = array( 'ttf', 'otf', 'woff', 'woff2' );
		if ( ! in_array( $file_extension, $allowed_font_files, true ) ) {
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Unsupported font file type',
				)
			);
		}

		$temp_dir = trailingslashit( $uploads['basedir'] ) .'kirki-font-temp/';
		wp_mkdir_p( $temp_dir );
		$temp_font = $temp_dir . wp_unique_filename( $temp_dir, $filename );

		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->move( $file['tmp_name'], $temp_font ) ) {
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Font upload failed',
				)
			);
		}

		$filetype      = wp_check_filetype_and_ext( $temp_font, $filename );
		$detected_ext  = ! empty( $filetype['ext'] ) ? strtolower( $filetype['ext'] ) : $file_extension;
		if ( empty( $detected_ext ) || ! in_array( $detected_ext, $allowed_font_files, true ) ) {
			wp_delete_file( $temp_font );
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Invalid font file type',
				),
			);
		}

		$font_meta          = self::get_font_metadata_from_filename( pathinfo( $filename, PATHINFO_FILENAME ) );
		if ( $family_override && $family_override !== $font_meta['family'] ) {
			$font_meta['family'] = $family_override;
		}
		$renamed_folder_name = self::normalize_font_family_slug( $font_meta['family'] );
		$font_folder         = trailingslashit( $uploads['basedir'] ) .'kirki-fonts/' . $renamed_folder_name;

		if ( is_dir( $font_folder ) ) {
			self::delete_dir( $font_folder );
		}

		wp_mkdir_p( $font_folder );

		$stored_font_name = wp_unique_filename( $font_folder, $filename );
		$wp_filesystem->move( $temp_font, $font_folder . '/' . $stored_font_name );

		$stylesheet = self::build_single_font_stylesheet( $font_meta, $stored_font_name, $detected_ext, $font_meta['family'] );
		$wp_filesystem->put_contents( $font_folder . '/stylesheet.css', $stylesheet );

		$data = array(
			'fontUrl'  => trailingslashit( $uploads['baseurl'] ) .'kirki-fonts/' . $renamed_folder_name . '/stylesheet.css',
			'family'   => $font_meta['family'],
			'variants' => array( $font_meta['variant'] ),
			'subsets'  => array( 'latin' ),
			'uploaded' => true,
			'version'  => 'v1',
		);

		wp_send_json(
			array(
				'status' => 'success',
				'data'   => $data,
			)
		);
	}

	private static function handle_font_zip_upload( $file, $filename, $wp_filesystem, $uploads ) {
		$temp_dir = trailingslashit( $uploads['basedir'] ) .'kirki-font-temp/';
		wp_mkdir_p( $temp_dir );

		$temp_zip = $temp_dir . wp_unique_filename( $temp_dir, $filename );

		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->move( $file['tmp_name'], $temp_zip ) ) {
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Zip file upload failed',
				)
			);
		}

		$zip = new ZipArchive();
		if ( $zip->open( $temp_zip ) !== true ) {
			wp_delete_file( $temp_zip );
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Invalid zip file',
				)
			);
		}

		$blocked_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'inc' );

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = wp_normalize_path( $zip->getNameIndex( $i ) );
			if ( strpos( $entry, '../' ) !== false || strpos( $entry, '..\\' ) !== false ) {
				$zip->close();
				wp_delete_file( $temp_zip );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Invalid zip contents',
					)
				);
			}

			$ext = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
			if ( $ext && in_array( $ext, $blocked_extensions, true ) ) {
				$zip->close();
				wp_delete_file( $temp_zip );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Executable files are not allowed',
					)
				);
			}
		}

		$folder_name = sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) );
		$upload_dir  = trailingslashit( $uploads['basedir'] ) .'kirki-fonts/' . $folder_name;
		wp_mkdir_p( $upload_dir );

		$zip->extractTo( $upload_dir );
		$zip->close();
		wp_delete_file( $temp_zip );

		if ( ! file_exists( $upload_dir . '/stylesheet.css' ) ) {
			self::delete_dir( $upload_dir );
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Invalid data',
				)
			);
		}

		$style_sheet_string = $wp_filesystem->get_contents( $upload_dir . '/stylesheet.css' );
		$font_family_name   = self::get_common_family_name( $style_sheet_string );

		if ( ! $font_family_name ) {
			self::delete_dir( $upload_dir );
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Font family name not found',
				)
			);
		}

		$result = self::get_rewritten_style_and_variants( $style_sheet_string, $font_family_name );
		$wp_filesystem->put_contents( $upload_dir . '/stylesheet.css', $result['css'] );

		$renamed_folder_name = self::normalize_font_family_slug( $font_family_name );
		$new_folder_path     = trailingslashit( $uploads['basedir'] ) .'kirki-fonts/' . $renamed_folder_name;

		if ( is_dir( $new_folder_path ) ) {
			self::delete_dir( $new_folder_path );
		}

		$wp_filesystem->move( $upload_dir, $new_folder_path );

		self::remove_extra_files_from_font_folder( $new_folder_path );

		wp_send_json(
			array(
				'status' => 'success',
				'data'   => array(
					'fontUrl'  => trailingslashit( $uploads['baseurl'] ) .'kirki-fonts/' . $renamed_folder_name . '/stylesheet.css',
					'family'   => $font_family_name,
					'variants' => $result['variants'],
					'subsets'  => array( 'latin' ),
					'uploaded' => true,
					'version'  => 'v1',
				),
			)
		);
	}

	private static function get_font_metadata_from_filename( $filename ) {
		$pattern           = strtolower( $filename );
		$style             = ( strpos( $pattern, 'italic' ) !== false || strpos( $pattern, 'oblique' ) !== false ) ? 'italic' : 'normal';
		$weight_keywords   = array(
			'extrablack' => '900',
			'black'      => '900',
			'heavy'      => '900',
			'extrabold'  => '800',
			'ultrabold'  => '800',
			'bold'       => '700',
			'semibold'   => '600',
			'demibold'   => '600',
			'medium'     => '500',
			'book'       => '400',
			'regular'    => '400',
			'normal'     => '400',
			'semilight'  => '300',
			'light'      => '300',
			'extralight' => '200',
			'ultralight' => '200',
			'thin'       => '100',
		);

		$weight = '400';
		foreach ( $weight_keywords as $keyword => $value ) {
			if ( strpos( $pattern, $keyword ) !== false ) {
				$weight = $value;
				break;
			}
		}

		if ( preg_match( '/(100|200|300|400|500|600|700|800|900)/', $pattern, $match ) ) {
			$weight = $match[1];
		}

		$clean_family = preg_replace( array( '/(italic|oblique)/i', '/(extrablack|black|heavy|extrabold|ultrabold|semibold|semilight|demibold|bold|medium|book|regular|normal|light|extralight|ultralight|thin)/i', '/(100|200|300|400|500|600|700|800|900)/' ), ' ', $filename );
		$clean_family = preg_replace( '/[-_]+/', ' ', $clean_family );
		$clean_family = trim( $clean_family );
		$family       = $clean_family ? ucwords( $clean_family ) : 'Custom Font';

		$variant = self::get_variant_from_weight_and_style( $weight, $style );

		return array(
			'family' => $family,
			'style'  => $style,
			'weight' => $weight,
			'variant'=> $variant,
		);
	}

	private static function get_variant_from_weight_and_style( $weight, $style ) {
		if ( '400' === $weight ) {
			return ( 'italic' === $style ) ? 'italic' : 'regular';
		}

		return ( 'italic' === $style ) ? $weight . 'italic' : $weight;
	}

	private static function build_single_font_stylesheet( $font_meta, $stored_font_name, $file_extension, $family_override = null ) {
		$formats = array(
			'ttf'  => 'truetype',
			'otf'  => 'opentype',
			'woff' => 'woff',
			'woff2'=> 'woff2',
		);

		$font_format = isset( $formats[ $file_extension ] ) ? $formats[ $file_extension ] : 'truetype';

		$family = $family_override ? $family_override : $font_meta['family'];

		return "@font-face {\n\tfont-family: '{$family}';\n\tfont-style: {$font_meta['style']};\n\tfont-weight: {$font_meta['weight']};\n\tfont-display: swap;\n\tsrc: url('{$stored_font_name}') format('{$font_format}');\n}\n";
	}

	private static function handle_multiple_raw_font_upload( $files, $wp_filesystem, $uploads ) {
		$allowed_font_files = array( 'ttf', 'otf', 'woff', 'woff2' );
		$temp_dir          = trailingslashit( $uploads['basedir'] ) .'kirki-font-temp/';
		wp_mkdir_p( $temp_dir );

		$common_family        = null;
		$common_family_slug   = null;
		$font_folder          = null;
		$renamed_folder_name  = null;
		$stylesheet           = '';
		$variants             = array();

		foreach ( $files as $single_file ) {
			$filename = sanitize_file_name( $single_file['name'] );
			$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, $allowed_font_files, true ) || empty( $single_file['tmp_name'] ) || ! is_uploaded_file( $single_file['tmp_name'] ) ) {
				self::cleanup_temp_files( $temp_dir );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Invalid font files provided',
					),
				);
			}

			$temp_font = $temp_dir . wp_unique_filename( $temp_dir, $filename );
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			if ( ! $wp_filesystem->move( $single_file['tmp_name'], $temp_font ) ) {
				self::cleanup_temp_files( $temp_dir );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Font upload failed',
					),
				);
			}

			$filetype     = wp_check_filetype_and_ext( $temp_font, $filename );
			$detected_ext = ! empty( $filetype['ext'] ) ? strtolower( $filetype['ext'] ) : $ext;
			if ( empty( $detected_ext ) || ! in_array( $detected_ext, $allowed_font_files, true ) ) {
				wp_delete_file( $temp_font );
				self::cleanup_temp_files( $temp_dir );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Invalid font file type',
					),
				);
			}

			$basename        = pathinfo( $filename, PATHINFO_FILENAME );
			$font_meta       = self::get_font_metadata_from_filename( $basename );
			$current_slug    = self::normalize_font_family_slug( $font_meta['family'] );
			$filename_slug   = self::get_family_hint_slug_from_filename( $basename );
			$comparison_slug = $filename_slug ? $filename_slug : $current_slug;
			if ( ! $common_family ) {
				$common_family       = $font_meta['family'];
				$common_family_slug  = $comparison_slug;
				$renamed_folder_name = $common_family_slug;
				$font_folder         = trailingslashit( $uploads['basedir'] ) .'kirki-fonts/' . $renamed_folder_name;

				if ( is_dir( $font_folder ) ) {
					self::delete_dir( $font_folder );
				}
				wp_mkdir_p( $font_folder );
			}

			if ( $comparison_slug !== $common_family_slug ) {
				wp_delete_file( $temp_font );
				self::delete_dir( $font_folder );
				self::cleanup_temp_files( $temp_dir );
				wp_send_json(
					array(
						'status'  => 'failure',
						'message' => 'Please upload fonts from the same family in a single batch.',
					),
				);
			}

			$stored_font_name = wp_unique_filename( $font_folder, $filename );
			$wp_filesystem->move( $temp_font, $font_folder . '/' . $stored_font_name );

			$stylesheet .= self::build_single_font_stylesheet( $font_meta, $stored_font_name, $detected_ext, $common_family );
			$variants[] = $font_meta['variant'];
		}

		self::cleanup_temp_files( $temp_dir );

		if ( empty( $stylesheet ) || ! $font_folder ) {
			wp_send_json(
				array(
					'status'  => 'failure',
					'message' => 'Unable to process fonts',
				),
			);
		}

		$wp_filesystem->put_contents( $font_folder . '/stylesheet.css', $stylesheet );

		$data = array(
			'fontUrl'  => trailingslashit( $uploads['baseurl'] ) .'kirki-fonts/' . $renamed_folder_name . '/stylesheet.css',
			'family'   => $common_family,
			'variants' => array_values( array_unique( $variants ) ),
			'subsets'  => array( 'latin' ),
			'uploaded' => true,
			'version'  => 'v1',
		);

		wp_send_json(
			array(
				'status' => 'success',
				'data'   => $data,
			),
		);
	}

	private static function cleanup_temp_files( $temp_dir ) {
		if ( is_dir( $temp_dir ) ) {
			$files = glob( trailingslashit( $temp_dir ) . '*' );
			if ( $files ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
	}

	private static function normalize_multiple_files( $files ) {
		if ( empty( $files ) || ! isset( $files['name'] ) || ! is_array( $files['name'] ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $files['name'] as $index => $name ) {
			if ( empty( $files['tmp_name'][ $index ] ) ) {
				continue;
			}

			$normalized[] = array(
				'name'     => $name,
				'type'     => $files['type'][ $index ],
				'tmp_name' => $files['tmp_name'][ $index ],
				'error'    => $files['error'][ $index ],
				'size'     => $files['size'][ $index ],
			);
		}

		return $normalized;
	}

	private static function normalize_font_family_slug( $family ) {
		$family = strtolower( $family );
		$slug   = preg_replace( '/[^a-z0-9]+/i', '-', $family );
		$slug   = trim( preg_replace( '/-+/', '-', $slug ), '-' );

		if ( ! $slug ) {
			$slug = sanitize_file_name( str_replace( ' ', '', $family ) );
		}

		return $slug;
	}

	private static function get_family_hint_slug_from_filename( $filename ) {
		if ( empty( $filename ) ) {
			return null;
		}

		$parts = preg_split( '/[-_]+/', $filename );
		if ( empty( $parts ) ) {
			return null;
		}

		if ( count( $parts ) < 2 ) {
			return null;
		}

		$base = $parts[0];

		return self::normalize_font_family_slug( $base );
	}


	public static function get_rewritten_style_and_variants( $css_string, $common_family_name ) {
				// Define weight keywords
				$weight_keywords = array(
					'extrablack' => '900',
					'black'      => '900',
					'heavy'      => '900',
					'extrabold'  => '800',
					'ultrabold'  => '800',
					'semibold'   => '600',
					'demibold'   => '600',
					'bold'       => '700',
					'medium'     => '500',
					'extralight' => '200',
					'ultralight' => '200',
					'light'      => '300',
					'book'       => '400',
					'regular'    => '400',
					'normal'     => '400',
					'thin'       => '100',
				);

					// Extract all @font-face blocks
					preg_match_all( '/@font-face\s*{[^}]*}/is', $css_string, $blocks );

					$rewritten_css = '';
					$variants      = array();

				foreach ( $blocks[0] as $block ) {
					$weight = null;
					$style  = 'normal';

					// get src
					preg_match( '/src:[^;]+;/i', $block, $src_match );
					$src = isset( $src_match[0] ) ? $src_match[0] : '';

					// get font-family
					preg_match( '/font-family:\s*[\'"]?([^;\'"]+)[\'"]?;/i', $block, $family_match );
					$family = isset( $family_match[1] ) ? $family_match[1] : '';

					// Detect weight from keywords in src or font-family only
					foreach ( $weight_keywords as $keyword => $w ) {
						if ( stripos( $src, $keyword ) !== false || stripos( $family, $keyword ) !== false ) {
								$weight = $w;
								break;
						}
					}

					// Fallback to declared CSS font-weight if not found
					if ( $weight === null ) {
						if ( preg_match( '/font-weight:\s*([0-9]+)/i', $block, $match ) ) {
								$weight = $match[1];
						} else {
								$weight = '400';
						}
					}

					if ( stripos( $block, 'italic' ) !== false ) {
							$style = 'italic';
					}

					// rewritten @font-face block
					$rewritten_css .= "@font-face {
								font-family: '{$common_family_name}';
								{$src}
								font-weight: {$weight};
								font-style: {$style};
						}\n\n";

					// if weight is 400, change -> regular, if italic 400italic -> italic, 200italic,700italic etc
					if ( $weight === '400' ) {
							$variant = ( $style === 'italic' ) ? 'italic' : 'regular';
					} else {
							$variant = ( $style === 'italic' ) ? $weight . 'italic' : $weight;
					}

					$variants[] = $variant;
				}

				// remove duplicate variants
				$variants = array_values( array_unique( $variants ) );

				return array(
					'css'      => $rewritten_css,
					'variants' => $variants,
				);
	}

	public static function get_common_family_name( $css_string ) {
		// get all font family names
		$pattern = '/font-family\s*:\s*([^;]+);/i';
		preg_match_all( $pattern, $css_string, $matches );

		if ( empty( $matches[1] ) ) {
			return null;
		}

		$base_names = array();

		foreach ( $matches[1] as $match ) {
			$parts = explode( ',', $match );
			foreach ( $parts as $p ) {
				$f = trim( $p, " \t\n\r\0\x0B'\"" );
				if ( ! $f ) {
					continue;
				}

				// normalize font family name
				$base = preg_replace( '/[-_\s]?(thin|extra|light|regular|medium|bold|black|italic|\d+)/i', '', $f );

				if ( $base ) {
					$base_names[] = strtolower( $base );
				}
			}
		}

		if ( empty( $base_names ) ) {
			return null;
		}

		// find most common
		$counts = array_count_values( $base_names );
		arsort( $counts ); // most frequent first
		$common_name = array_key_first( $counts );
		$common_name = str_replace( array( '_', '-' ), ' ', $common_name );
		$common_name = ucwords( $common_name );

		return $common_name;
	}

	/**
	 * Remove extra files from font folder.
	 * we need only stylesheet.css, .woff and .woff2 file.
	 * others files will be removed.
	 *
	 * @param string $folder_path //raw path of uploaded folder.
	 * @return void
	 */
	private static function remove_extra_files_from_font_folder( $folder_path ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->is_dir( $folder_path ) ) {
			$files = $wp_filesystem->dirlist( $folder_path );

			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					$file_path = $folder_path . '/' . $file['name'];

					if ( 'f' === $file['type'] ) {
						$ext = pathinfo( $file_path, PATHINFO_EXTENSION );
						$allowed = array( 'css', 'woff', 'woff2', 'ttf', 'otf' );

						if ( ! in_array( strtolower( $ext ), $allowed, true ) ) {
							wp_delete_file( $file_path );
						}
					}
				}
			}
		}
	}


	/**
	 * Remove custom font folder from server
	 *
	 * @return void wp_send_json.
	 */
	public static function remove_custom_font_folder_from_server() {        //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = HelperFunctions::sanitize_text( isset( $_POST['data'] ) ? $_POST['data'] : null );
		if ( ! $data ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Invalid post data',
				)
			);
			die();
		}
		$fonts = json_decode( stripslashes( $data ), true );

		foreach ( $fonts as $key => $value ) {
			$upload_root_dir = wp_upload_dir()['basedir'];
			$upload_dir      = $upload_root_dir . '/' .'kirki-fonts/' . $value['family'];
			if ( is_dir( $upload_dir ) ) {
				self::delete_dir( $upload_dir );
			}

			// Remove font from local.
			$font_family_slug = sanitize_title_with_dashes( $value['family'] );
			$font_local_dir   = WP_CONTENT_DIR . "/uploads/kirki-fonts/{$font_family_slug}";

			if ( is_dir( $font_local_dir ) ) {
				HelperFunctions::delete_directory( $font_local_dir );
			}
		}
		wp_send_json(
			array(
				'status'  => 'success',
				'message' => 'Font folder deleted success',
				'url'     => $upload_dir,
			)
		);
	}

	/**
	 * Delete Dir
	 *
	 * @param string $dir_path directory path string.
	 * @throws InvalidArgumentException If the $dir_path is not a directory.
	 * @return void
	 */
	public static function delete_dir( $dir_path ) {
		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		if ( ! is_dir( $dir_path ) ) {
			return;
		}
		$wp_filesystem->delete( $dir_path, true );
	}

	/**
	 * Get Font Family name using regex
	 *
	 * @param string $css_string css string.
	 * @return string font family name.
	 */
	public static function get_font_family_name_using_regex( $css_string ) {
		$pattern = '/font-family:.*?;/';
		preg_match( $pattern, $css_string, $matches );
		$font_family = $matches[0];
		$font_family = str_replace( 'font-family:', '', $font_family );
		$font_family = str_replace( ';', '', $font_family );
		$font_family = str_replace( "'", '', $font_family );
		$font_family = trim( $font_family );
		return $font_family;
	}

	/**
	 * Upload base64 image
	 *
	 * @return void wp send json
	 */
	public static function upload_base64_img() {        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$source     = isset( $_POST['source'] ) ? $_POST['source'] : '';
		$image_name = isset( $_POST['imageName'] ) ? $_POST['imageName'] : '';

		$source     = HelperFunctions::sanitize_text( $source );
		$image_name = HelperFunctions::sanitize_text( $image_name );

		if ( empty( $source ) ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'No image data provided',
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Configuration
		|--------------------------------------------------------------------------
		*/
		$max_size_bytes = 5 * 1024 * 1024; // 5MB decoded limit
		$allowed_mimes  = array(
			'image/png'  => 'png',
			'image/jpeg' => 'jpg',
			'image/webp' => 'webp',
		);

		/*
		|--------------------------------------------------------------------------
		| Parse base64 header safely
		|--------------------------------------------------------------------------
		*/
		if ( ! preg_match( '#^data:(image\/[a-zA-Z0-9.+-]+);base64,#', $source, $matches ) ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Invalid base64 image format',
				)
			);
		}

		$mime = strtolower( $matches[1] );

		if ( ! isset( $allowed_mimes[ $mime ] ) ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Unsupported image type',
				)
			);
		}

		$base64 = substr( $source, strpos( $source, ',' ) + 1 );
		$base64 = str_replace( ' ', '+', $base64 );

		/*
		|--------------------------------------------------------------------------
		| Enforce decoded size quota (before decode)
		|--------------------------------------------------------------------------
		| Base64 expands data by ~33%, so estimate first
		*/
		$estimated_size = (int) ( strlen( $base64 ) * 0.75 );
		if ( $estimated_size > $max_size_bytes ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Image exceeds maximum allowed size',
				)
			);
		}

		$decoded = base64_decode( $base64, true );

		if ( $decoded === false ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Invalid base64 data',
				)
			);
		}

		if ( strlen( $decoded ) > $max_size_bytes ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Image exceeds maximum allowed size',
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Re-encode image to strip metadata & polyglots
		|--------------------------------------------------------------------------
		*/
		$image = @imagecreatefromstring( $decoded );
		if ( ! $image ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Image decoding failed',
				)
			);
		}

		ob_start();
		switch ( $mime ) {
			case 'image/png':
				imagepng( $image, null, 9 );
				break;
			case 'image/jpeg':
				imagejpeg( $image, null, 90 );
				break;
			case 'image/webp':
				imagewebp( $image, null, 90 );
				break;
		}
		$clean_image = ob_get_clean();
		imagedestroy( $image );

		if ( ! $clean_image ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Failed to process image',
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Save using WordPress upload system
		|--------------------------------------------------------------------------
		*/
		$extension = $allowed_mimes[ $mime ];
		$filename  = $image_name
			? sanitize_file_name( $image_name ) . '.' . $extension
			: 'base64-image-' . gmdate( 'Y-m-d-His' ) . '.' . $extension;

		$upload = wp_upload_bits( $filename, null, $clean_image );

		if ( ! empty( $upload['error'] ) ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Error saving image',
				)
			);
		}

		$file = array(
			'name'     => basename( $upload['file'] ),
			'type'     => $mime,
			'tmp_name' => $upload['file'],
			'error'    => 0,
			'size'     => filesize( $upload['file'] ),
		);

		$attachment_id = self::upload_single_media( $file );

		if ( ! $attachment_id ) {
			wp_send_json(
				array(
					'status'  => 'fail',
					'message' => 'Failed to create media attachment',
				)
			);
		}

		$img = wp_get_attachment_image_src( $attachment_id, 'full' );

		wp_send_json(
			array(
				'status' => 'success',
				'src'    => $img[0],
				'id'     => $attachment_id,
			)
		);
	}

	/**
	 * Validate a svg file
	 *
	 * @param string $svg_file svg file path.
	 * @return bool
	 */
	private function validate_svg( $svg_file ) {
		// File sanity checks
		if ( ! file_exists( $svg_file ) || ! is_readable( $svg_file ) ) {
			return false;
		}

		$svg = file_get_contents( $svg_file );
		if ( $svg === false ) {
			return false;
		}

		// Quick check to avoid non-SVG files (existing behavior)
		if ( stripos( $svg, '<svg' ) === false ) {
			return false;
		}

		// Initialize sanitizer
		$sanitizer = new Sanitizer();

		// Security hardening
		$sanitizer->removeRemoteReferences( true ); // blocks external <use>, <image>, etc.
		$sanitizer->minify( true );

		// IMPORTANT:
		// Do NOT call setAllowedTags() or setAllowedAttrs()
		// The built-in allowlist is already safe and complete.

		$clean_svg = $sanitizer->sanitize( $svg );

		if ( $clean_svg === false ) {
			return false; // Sanitization failed
		}

		// Final validation using DOM
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );

		if ( ! $dom->loadXML( $clean_svg, LIBXML_NONET ) ) {
			return false;
		}

		// Ensure root element is <svg>
		if ( $dom->documentElement->nodeName !== 'svg' ) {
			return false;
		}

		return true; // SVG is sanitized and safe
	}

}