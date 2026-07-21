<?php

namespace Kirki\App\Services;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\DTO\GoogleFontDTO;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\App\Supports\FileHandler;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Supports\Facades\File;
use Kirki\Framework\Supports\Facades\Http;

use function Kirki\App\get_upload_directory;
use function Kirki\App\get_upload_directory_url;
use function Kirki\Framework\clean_path;

class FontService
{
    public function download_google_font(GoogleFontDTO $payload)
    {
		// Extra security: keep only a-z, 0-9, and hyphens
		$font_family_slug = preg_replace('/[^a-z0-9\-]/i', '', sanitize_title_with_dashes(basename($payload->family)));

        if (empty($font_family_slug) || strpos($font_family_slug, '..') !== false) {
            throw new Exception(esc_html__('Not valid font family.', 'kirki'), Response::FORBIDDEN);
        }

        $has_write_permission = File::is_writable(get_upload_directory());

        if (!$has_write_permission) {
            throw new Exception(esc_html__('Upload directory is not writable.', 'kirki'), Response::FORBIDDEN);
        }

        $font_dir = clean_path(get_upload_directory() . "/kirki-fonts/{$font_family_slug}", false);
        $css_file_path = clean_path($font_dir . "/{$font_family_slug}.css", false);

        FileHandler::verify_directory_traversal($css_file_path);

        if (File::exists($css_file_path)) {
            throw new Exception(esc_html__('Font already downloaded.', 'kirki'), Response::FORBIDDEN);
        }

        $font_url_parts = wp_parse_url($payload->fontUrl);

        if (!$font_url_parts) {
            throw new Exception(esc_html__('Invalid URL.', 'kirki'));
        }

        if (($font_url_parts['scheme'] ?? '') !== 'https') {
            throw new Exception(esc_html__('Only HTTPS is allowed.', 'kirki'));
        }

        if (($font_url_parts['host'] ?? '') !== 'fonts.googleapis.com') {
            throw new Exception(esc_html__('Only Google Fonts is supported.', 'kirki'));
        }

        try {
            if (!File::is_directory($font_dir)) {
				File::make_dir($font_dir);
			}

            $response = Http::with_options([
                'redirection' => 0
            ])->get($payload->fontUrl);

            if ($response->failed()) {
                throw new Exception(esc_html__('Failed to fetch Google Fonts CSS.', 'kirki'));
            }

            if (isset($payload->files['regular'])) {
				$payload->files['400'] = $payload->files['regular'];
				unset($payload->files['regular']);
			}

            $formats = [
                'woff2' => 'woff2',
				'woff'  => 'woff',
				'ttf'   => 'truetype',
				'otf'   => 'opentype',
            ];
            $local_css = '';

            foreach ($payload->files as $weight => $url) {
				$allowed_ext = array_keys($formats);
				$extension   = strtolower(pathinfo($url, PATHINFO_EXTENSION));

				if (!in_array($extension, $allowed_ext, true)) {
                    /* translators: %s: file extension */
                    throw new Exception(sprintf(esc_html__('Invalid or unsafe file extension: %s.', 'kirki'), $extension));
				}

                $url_parts = wp_parse_url($url);

                if (!$url_parts) {
                    throw new Exception(esc_html__('Invalid URL.', 'kirki'));
                }

                if (($url_parts['scheme'] ?? '') !== 'https') {
                    throw new Exception(esc_html__('Only HTTPS is allowed.', 'kirki'));
                }

                if (($url_parts['host'] ?? '') !== 'fonts.gstatic.com') {
                    throw new Exception(esc_html__('Only Google Fonts is supported.', 'kirki'));
                }

				$file_name = "{$weight}.{$extension}";
				$file_path = clean_path($font_dir . '/' . $file_name, false);

                FileHandler::verify_directory_traversal($file_path);

				$font_response = Http::with_options([
                    'redirection' => 0
                ])->get($url);

				if ($font_response->failed()) {
					throw new Exception(esc_html__('Failed to fetch font file.', 'kirki'));
				}

                File::put($file_path, $font_response->__toString());

				$format = $formats[$extension] ?? 'truetype';
				$local_css .= "@font-face {
						font-family: '{$payload->family}';
						font-style: normal;
						font-weight: {$weight};
						font-display: swap;
						src: url('{$file_name}') format('{$format}');
				}\n";
			}

			if (empty($local_css)) {
				throw new Exception(esc_html__('No usable font files were downloaded.', 'kirki'));
			}

            File::put($css_file_path, $local_css);
			$payload->localUrl = clean_path(get_upload_directory_url() . "/kirki-fonts/{$font_family_slug}/{$font_family_slug}.css", false);

            $this->save_google_font_into_global_custom_fonts($payload);

            return $payload;

        } catch (Exception $exception) {
            if (File::is_directory($font_dir)) {
				File::delete($font_dir);
			}

            throw $exception;
        }
    }

    public function remove_google_font(GoogleFontDTO $payload) {
        $font_family_slug = sanitize_title_with_dashes(basename($payload->family));
		$font_dir = clean_path(get_upload_directory() . "/kirki-fonts/{$font_family_slug}", false);

        FileHandler::verify_directory_traversal($font_dir);

        if (File::is_directory($font_dir)) {
            File::delete($font_dir);
        }

        $payload->exclude(['localUrl']);

        $this->save_google_font_into_global_custom_fonts($payload);

        return true;
    }

    protected function save_google_font_into_global_custom_fonts(GoogleFontDTO $font) {
		// first get the font data
		$custom_fonts = GlobalData::get_global_custom_fonts();

		if (empty($custom_fonts)) {
			$custom_fonts = [];
		}

		if (!empty($font->family)) {
			$custom_fonts[$font->family] = $font->to_array();

            GlobalData::update_global_custom_fonts($custom_fonts);
		}
	}

    public function remove_custom_fonts_permanently_from_directory(array $fonts)
    {
        foreach ($fonts as $font) {
			// Remove font from local.
			$font_family_slug = sanitize_title_with_dashes(basename($font['family']));
            $font_local_dir = clean_path(get_upload_directory() . "/kirki-fonts/{$font_family_slug}", false);

            FileHandler::verify_directory_traversal($font_local_dir);

            if (File::is_directory($font_local_dir)) {
                File::delete($font_local_dir);
            }
		}
    }
}