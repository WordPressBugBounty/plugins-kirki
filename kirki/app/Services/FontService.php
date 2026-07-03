<?php

namespace Kirki\App\Services;

use Exception;
use Kirki\App\DTO\GoogleFontDTO;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Supports\Facades\File;
use Kirki\Framework\Supports\Facades\Http;

use function Kirki\App\get_upload_directory;
use function Kirki\App\get_upload_directory_url;

defined('ABSPATH') || exit;

class FontService
{
    public static function create()
    {
        return new static();
    }

    public function download_google_font(GoogleFontDTO $payload)
    {
		// Extra security: keep only a-z, 0-9, and hyphens
		$font_family_slug = preg_replace('/[^a-z0-9\-]/i', '', sanitize_title_with_dashes($payload->family));

        if (empty($font_family_slug) || strpos($font_family_slug, '..') !== false) {
            throw new Exception(esc_html__('Not valid font family.', 'kirki'), Response::FORBIDDEN);
        }

        $has_write_permission = File::is_writable(get_upload_directory());

        if (!$has_write_permission) {
            throw new Exception(esc_html__('Upload directory is not writable.', 'kirki'), Response::FORBIDDEN);
        }

        $font_dir = get_upload_directory() . "/kirki-fonts/{$font_family_slug}";
        $css_file_path = $font_dir . "/{$font_family_slug}.css";

        if (File::exists($css_file_path)) {
            throw new Exception(esc_html__('Font already downloaded.', 'kirki'), Response::FORBIDDEN);
        }

        if (stripos($payload->fontUrl, 'fonts.googleapis.com') === false) {
            throw new Exception(esc_html__('Only Google Fonts is supported.', 'kirki'), Response::FORBIDDEN);
        }

        try {
            if (!File::is_directory($font_dir)) {
				File::make_dir($font_dir);
			}

            $css = Http::get($payload->fontUrl);

            if ($css->failed()) {
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
					throw new Exception(esc_html(sprintf(__('Invalid or unsafe file extension: %s.', 'kirki'), $extension)));
				}

				$file_name = "{$weight}.{$extension}";
				$file_path = $font_dir . '/' . $file_name;

				$font_data = Http::get($url);

				if ($font_data->failed()) {
					throw new Exception(esc_html__('Failed to fetch font file.', 'kirki'));
				}

                File::put($file_path, $font_data->__toString());

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
			$payload->localUrl = get_upload_directory_url() . "/kirki-fonts/{$font_family_slug}/{$font_family_slug}.css";

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
        $font_family_slug = sanitize_title_with_dashes( basename( $payload->family ) );
		$font_dir = get_upload_directory() . "/kirki-fonts/{$font_family_slug}";

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

    public function remove_custom_fonts_permanently(array $fonts)
    {
        foreach ($fonts as $font) {
			$upload_dir = get_upload_directory() . '/kirki-fonts/' . $font['family'];

            if (File::is_directory($upload_dir)) {
                File::delete($upload_dir);
            }

			// Remove font from local.
			$font_family_slug = sanitize_title_with_dashes($font['family']);
            $font_local_dir = get_upload_directory() . "/kirki-fonts/{$font_family_slug}";

            if (File::is_directory($font_local_dir)) {
                File::delete($font_local_dir);
            }
		}
    }
}