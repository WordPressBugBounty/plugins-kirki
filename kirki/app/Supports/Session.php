<?php
/**
 * Session support
 *
 * Lightweight, cookie-identified session store backed by WordPress transients.
 * Replaces the deprecated session helpers in {@see \Kirki\HelperFunctions}. The
 * cookie name, transient key and expirations are kept identical so this stays
 * interoperable with data written by the legacy helpers (e.g. the front-end
 * preview renderer).
 *
 * @package kirki
 */

namespace Kirki\App\Supports;

use Kirki\Framework\Sanitizer;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Session
{
	/**
	 * Cookie name that stores the session identifier.
	 *
	 * @var string
	 */
	public const COOKIE_NAME = 'kirki_session_id';

	/**
	 * Prefix used for the session transient key.
	 *
	 * @var string
	 */
	public const TRANSIENT_PREFIX = 'kirki_session_';

	/**
	 * In-request cache of the resolved session id.
	 *
	 * @var string|null
	 */
	protected static $session_id = null;

	/**
	 * Get the current session id, generating and persisting one when missing.
	 *
	 * @return string
	 */
	public static function get_session_id()
	{
		if (static::$session_id) {
			return static::$session_id;
		}

		if (isset($_COOKIE[static::COOKIE_NAME])) {
			static::$session_id = Sanitizer::apply_rule(wp_unslash($_COOKIE[static::COOKIE_NAME]), Sanitizer::TEXT);

			return static::$session_id;
		}

		static::$session_id = wp_generate_uuid4();

		setcookie(
			static::COOKIE_NAME,
			static::$session_id,
			time() + (DAY_IN_SECONDS * 7),
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		return static::$session_id;
	}

	/**
	 * Get a value from the session store.
	 *
	 * @param string $key     The key to retrieve.
	 * @param mixed  $default Value returned when the key is absent.
	 * @return mixed
	 */
	public static function get(string $key, $default = null)
	{
		$session_data = get_transient(static::TRANSIENT_PREFIX . static::get_session_id());

		if (is_array($session_data) && isset($session_data[$key])) {
			return $session_data[$key];
		}

		return $default;
	}

	/**
	 * Add or update a value in the session store.
	 *
	 * @param string $key   The key of the session data.
	 * @param mixed  $value The value of the session data.
	 * @return void
	 */
	public static function put(string $key, $value)
	{
		$transient_key = static::TRANSIENT_PREFIX . static::get_session_id();
		$session_data = get_transient($transient_key) ?: [];

		$session_data[$key] = $value;

		set_transient($transient_key, $session_data, DAY_IN_SECONDS);
	}

	/**
	 * Remove a value from the session store.
	 *
	 * @param string $key The key of the session data to delete.
	 * @return void
	 */
	public static function forget(string $key)
	{
		$transient_key = static::TRANSIENT_PREFIX . static::get_session_id();
		$session_data = get_transient($transient_key);

		if (is_array($session_data) && isset($session_data[$key])) {
			unset($session_data[$key]);
			set_transient($transient_key, $session_data, DAY_IN_SECONDS);
		}
	}
}
