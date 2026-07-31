<?php

namespace Kirki\App\Services;

use Kirki\Framework\Filesystem\UploadedFile;
use function Kirki\Framework\app;
use function Kirki\Framework\user;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\FormActions\FormActionDispatcher;
use Kirki\App\DTO\Form\FormConfigDTO;
use Kirki\App\Models\Form;
use Kirki\App\Models\FormData;
use Kirki\App\Supports\ActionHooks;
use Kirki\App\Supports\Form\FormFieldRulesBuilder;
use Kirki\App\Supports\Recaptcha;
use Kirki\App\Supports\Session;
use Kirki\Framework\Exceptions\ValidationException;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Validation\Validator;
use WP_Error;

/**
 * Orchestrates a front-end form submission.
 */
class FormSubmissionService
{
	/**
	 * Request keys that are submission metadata, not form field data.
	 *
	 * @var array
	 */
	protected const ADDITIONAL_KEYS = [
		'_kirki_form',
		'_wpnonce',
		'_wp_http_referer',
		'g-recaptcha-token',
		'g-recaptcha-response',
	];

	/**
	 * @var FormActionDispatcher
	 */
	protected $actions;

	public function __construct()
	{
		$this->actions = app(FormActionDispatcher::class); // @todo: resolve using DI once the router starts using container
	}

	/**
	 * Handle a form submission.
	 *
	 * @param array $params The full request payload.
	 * @param array UploadedFile[] the file array of our own file class
	 * @return bool Whether every configured action (email/webhook/...) succeeded.
	 *
	 * @throws ValidationException When field validation fails.
	 * @throws Exception           When the form metadata/configuration is invalid, a
	 *                             submission limit has been reached, or the submission
	 *                             could not be saved.
	 */
	public function handle(array $params, array $files = [])
	{
		Recaptcha::verify($params['g-recaptcha-token'] ?? null);

		$form_data = $this->extract_form_data($params);
		['form_id' => $form_id, 'post_id' => $post_id] = $this->parse_form_metadata($params['_kirki_form'] ?? '');
		$form_config = $this->load_form_config($form_id, $post_id);

		$form_data = $this->validate_fields($form_data, $form_config->fields, $files);

		$form = $this->save_form($form_id, $post_id, $form_config);
		$session_id = Session::get_session_id(); // todo: need to fix this for user ip

		$this->enforce_submission_limits($form->id, $session_id, $form_config);

		$form_data = $this->process_file_uploads($form_data);
		$this->save_submission($form->id, $form_data, $form_config, $session_id);

		$actions_succeeded = $this->actions->dispatch($form_data, $form_config);

		if (!$actions_succeeded) {
			throw new Exception(esc_html__('One or more form actions failed. Please try again.', 'kirki'), (int) Response::INTERNAL_SERVER_ERROR);
		}

		ActionHooks::kirki_form_submitted($form_data, $form_config);

		return true;
	}

	/**
	 * Extract only the form data from the request payload.
	 *
	 * @param array $params The full request payload.
	 * @return array The form data without additional keys.
	 */
	protected function extract_form_data(array $params)
	{
		$form_data = $params;

		foreach (static::ADDITIONAL_KEYS as $key) {
			unset($form_data[$key]);
		}

		return $form_data;
	}

	/**
	 * Parse and decode the base64 form metadata token.
	 *
	 * @param mixed $form_meta_data_base64 Base64 encoded form metadata.
	 * @return array{form_id: string|null, post_id: string|null}
	 */
	protected function parse_form_metadata($form_meta_data_base64)
	{
		if (!is_string($form_meta_data_base64)) {
			return ['form_id' => null, 'post_id' => null];
		}

		$form_meta_data = explode('|', base64_decode(base64_decode($form_meta_data_base64)));

		return [
			'form_id' => $form_meta_data[0] ?: null,
			'post_id' => $form_meta_data[1] ?: null,
		];
	}

	/**
	 * Resolve the stored form configuration for a submission.
	 *
	 * @param string|null $form_id The form element id.
	 * @param string|null $post_id The id of the post the form is rendered on.
	 * @return FormConfigDTO The form configuration.
	 *
	 * @throws Exception When the metadata or configuration is invalid.
	 */
	protected function load_form_config($form_id, $post_id)
	{
		if (!isset($form_id, $post_id)) {
			throw new Exception(esc_html__('Form data is invalid!', 'kirki'), (int) Response::BAD_REQUEST);
		}

		$form_config = Session::get($form_id);

		if (!is_array($form_config)) {
			throw new Exception(esc_html__('Form config not found', 'kirki'), (int) Response::BAD_REQUEST);
		}

		return FormConfigDTO::from_array($form_config);
	}

	/**
	 * Validate the submission against the configured fields.
	 *
	 * @param array $form_data The submission data.
	 * @param array $fields    The field configuration.
	 * @param UploadedFile[] $files     The file array of our own file class.
	 * @return array The validated form data.
	 *
	 * @throws ValidationException When validation fails.
	 */
	protected function validate_fields(array $form_data, array $fields, array $files = [])
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data = FormFieldRulesBuilder::data_for_validation($form_data, $fields, $files);

		Validator::make($data, FormFieldRulesBuilder::rules($fields))->validate(); //@todo: not all data are coming

		return $data;
	}

	/**
	 * Find or create the stored form record, keeping its name in sync.
	 *
	 * @param string        $form_id     The form element id.
	 * @param string        $post_id     The id of the post the form is rendered on.
	 * @param FormConfigDTO $form_config The form configuration.
	 * @return Form
	 */
	protected function save_form($form_id, $post_id, FormConfigDTO $form_config)
	{
		$form_name = $form_config->name;

		$saved = Form::update_or_create([
			'post_id' => (int) $post_id,
			'form_ele_id' => $form_id,
		], [
			'name' => $form_name,
		]);

		return $saved;
	}

	/**
	 * Reject the submission if it hits a configured entry or response limit.
	 *
	 * @param int           $form_id     The stored form id.
	 * @param string        $session_id  The Kirki session id (cookie-identified, see Session::get_session_id()).
	 * @param FormConfigDTO $form_config The form configuration.
	 * @return void
	 *
	 * @throws Exception When a limit has been reached.
	 */
	protected function enforce_submission_limits($form_id, $session_id, FormConfigDTO $form_config)
	{
		$max_entry = $form_config->maxEntry;
		$entry_limit = !empty($max_entry['restricted']) ? (int) $max_entry['value'] : null;

		if ($this->entry_limit_reached($form_id, $session_id, $entry_limit)) {
			throw new Exception(esc_html__('You have reached the maximum number of submissions allowed.', 'kirki'), (int) Response::TOO_MANY_REQUESTS);
		}

		$response_limit = $form_config->responseLimit;
		$response_limit = !empty($response_limit['restricted']) ? (int) $response_limit['value'] : null;

		if ($this->response_limit_reached($form_id, $response_limit)) {
			throw new Exception(esc_html__('This form is no longer accepting submissions.', 'kirki'), (int) Response::TOO_MANY_REQUESTS);
		}
	}

	/**
	 * Whether the current session has reached the per-session entry limit.
	 *
	 * @param int      $form_id    The stored form id.
	 * @param string   $session_id The Kirki session id (cookie-identified, see Session::get_session_id()).
	 * @param int|null $limit      The entry limit.
	 * @return bool
	 */
	protected function entry_limit_reached($form_id, $session_id, $limit)
	{
		if ($limit === null) {
			return false;
		}

		$count = FormData::where('form_id', $form_id)
			->where('session_id', $session_id)
			->distinct()
			->count('timestamp');

		return $count >= intval($limit);
	}

	/**
	 * Whether the form has reached its total response limit.
	 *
	 * @param int      $form_id The stored form id.
	 * @param int|null $limit   The response limit.
	 * @return bool
	 */
	protected function response_limit_reached($form_id, $limit)
	{
		if ($limit === null) {
			return false;
		}

		$count = FormData::where('form_id', $form_id)
			->distinct()
			->count('timestamp');

		return $count >= intval($limit);
	}

	/**
	 * Process file uploads from the submission.
	 *
	 * By the time this runs, validate_fields() has already rejected any file
	 * with an invalid type or size, so this only has to persist the upload.
	 *
	 * @param array $form_data Form data array.
	 * @return array Modified form data with attachment IDs.
	 *
	 * @throws Exception When file upload fails.
	 */
	protected function process_file_uploads($form_data)
	{
		foreach ($form_data as $name => $data) {
			if (!$data instanceof UploadedFile) {
				continue;
			}

			$result = $this->upload_file_to_media($name);

			if (is_wp_error($result)) {
				throw new Exception($result->get_error_message(), Response::INTERNAL_SERVER_ERROR);
			}

			$form_data[$name] = $result;
		}

		return $form_data;
	}

	/**
	 * Upload a file to the WordPress media library.
	 *
	 * @param string $name File input name.
	 * @return int|WP_Error Attachment ID or error.
	 */
	protected function upload_file_to_media($name)
	{
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		return media_handle_upload($name, 0);
	}

	/**
	 * Persist the submission, honouring the form's saveData preference.
	 *
	 * @param int           $form_id     Stored form id.
	 * @param array         $form_data   Form data.
	 * @param FormConfigDTO $form_config Form configuration.
	 * @param string        $session_id  Kirki session id.
	 * @return void
	 *
	 * @throws Exception When saveData is enabled but the submission could not be stored.
	 */
	protected function save_submission($form_id, $form_data, FormConfigDTO $form_config, $session_id)
	{
		if (!$form_config->saveData) {
			return;
		}

		if (!$this->insert_form_data($form_data, $form_id, $form_config->fields, $session_id)) {
			throw new Exception(esc_html__('Failed to save your submission. Please try again.', 'kirki'), (int) Response::INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Insert submission data — one row per field, grouped by timestamp + session.
	 *
	 * @param array  $form_data       Form data.
	 * @param int    $form_id         Stored form id.
	 * @param array  $form_data_types Field configuration (for input_type).
	 * @param string $session_id      Kirki session id.
	 * @return bool
	 */
	protected function insert_form_data($form_data, $form_id, $form_data_types, $session_id)
	{
		if (empty($form_data)) {
			return false;
		}

		$timestamp = time();
		$user_id = user()->get_id();

		$rows = [];

		foreach ($form_data as $name => $value) {
			$type = isset($form_data_types[$name]['type']) ? $form_data_types[$name]['type'] : 'text';

			if (is_array($value)) {
				$value = maybe_serialize($value);
			}

			$rows[] = [
				'form_id' => $form_id,
				'user_id' => $user_id,
				'session_id' => $session_id,
				'timestamp' => $timestamp,
				'input_key' => (string) $name,
				'input_value' => $value,
				'input_type' => (string) $type,
			];
		}

		return FormData::insert($rows);
	}
}
