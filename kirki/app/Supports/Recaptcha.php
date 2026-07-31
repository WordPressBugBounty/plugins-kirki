<?php
/**
 * Google reCAPTCHA support
 *
 * Verifies a reCAPTCHA token against Google's siteverify endpoint using the
 * keys configured in the Kirki admin common data option.
 *
 * @package kirki
 */

namespace Kirki\App\Supports;

use Kirki\App\Constants\OptionKeys;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Supports\Facades\Http;
use Kirki\Framework\Supports\Facades\Option;
use RuntimeException;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Recaptcha
{
	/**
	 * Google reCAPTCHA verification endpoint.
	 *
	 * @var string
	 */
	public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * Verify a reCAPTCHA token.
	 *
	 * No-op when no token is supplied (matches the legacy behaviour where the
	 * check only runs if the front-end sent a token). Throws on misconfiguration
	 * or a failed verification so the caller can abort the request.
	 *
	 * @param string|null $token The reCAPTCHA token from the submission.
	 * @return void
	 *
	 * @throws RuntimeException When configuration is missing or verification fails.
	 */
	public static function verify(?string $token = null)
	{
		if (empty($token)) {
			return;
		}

		$common_data = Option::get(OptionKeys::WP_ADMIN_COMMON_DATA, [], false);

		if (!isset($common_data['recaptcha']['GRC_version'])) {
			throw new RuntimeException(
				esc_html__('reCAPTCHA configuration not found', 'kirki'),
				(int) Response::BAD_REQUEST
			);
		}

		$version = $common_data['recaptcha']['GRC_version'];
		$recaptcha = $common_data['recaptcha'][$version] ?? [];
		$secret_key = $recaptcha['GRC_secret_key'] ?? '';

		if (empty($secret_key)) {
			throw new RuntimeException(
				esc_html__('reCAPTCHA secret key not configured', 'kirki'),
				(int) Response::BAD_REQUEST
			);
		}

		if (!static::is_token_valid($secret_key, $token)) {
			throw new RuntimeException(
				esc_html__('Google reCAPTCHA verification failed', 'kirki'),
				(int) Response::BAD_REQUEST
			);
		}
	}

	/**
	 * Call Google's siteverify endpoint for the given token.
	 *
	 * @param string $secret_key The reCAPTCHA secret key.
	 * @param string $token      The reCAPTCHA token.
	 * @return bool
	 */
	protected static function is_token_valid(string $secret_key, string $token)
	{
		$response = Http::as_form()->post(
			static::VERIFY_URL,
			[
				'secret' => $secret_key,
				'response' => $token,
			]
		);

		if ($response->failed()) {
			return false;
		}

		return (bool) $response->json('success');
	}
}
