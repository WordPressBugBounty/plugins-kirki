<?php

namespace Kirki\App\Services;

use function Kirki\Framework\app;
use function Kirki\Framework\user;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\Form\FormFieldTypes;
use Kirki\App\DTO\Form\FormConfigDTO;
use Kirki\App\FormActions\FormActionDispatcher;
use Kirki\App\Models\Form;
use Kirki\App\Models\FormData;
use Kirki\App\Supports\ActionHooks;
use Kirki\App\Supports\Form\FormFieldRulesBuilder;
use Kirki\App\Supports\Recaptcha;
use Kirki\App\Supports\Session;
use Kirki\Framework\Exceptions\ValidationException;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Validation\Validator;

/**
 * Orchestrates a front-end form submission.
 */
class FormSubmissionService
{
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
	 * @return bool Whether every configured action (email/webhook/...) succeeded.
	 *
	 * @throws ValidationException When field validation fails.
	 * @throws Exception           When the form metadata/configuration is invalid, a
	 *                             submission limit has been reached, or the submission
	 *                             could not be saved.
	 */
	public function handle(array $params)
	{
		Recaptcha::verify($params['g-recaptcha-token'] ?? null);

		['form_id' => $form_id, 'post_id' => $post_id] = $this->parse_form_metadata($params['_kirki_form'] ?? '');
		$form_config = $this->load_form_config($form_id, $post_id);

		$form_data = $this->extract_form_data($params, $form_config->fields);

		$form_data = $this->validate_fields($form_data, $form_config->fields);

		$form = $this->save_form($form_id, $post_id, $form_config);
		$session_id = Session::get_session_id(); // todo: need to fix this for user ip

		$this->enforce_submission_limits($form->id, $session_id, $form_config);

		$this->save_submission($form->id, $form_data, $form_config, $session_id);

		$actions_succeeded = $this->actions->dispatch($form_data, $form_config);

		if (!$actions_succeeded) {
			throw new Exception(esc_html__('One or more form actions failed. Please try again.', 'kirki'), (int) Response::INTERNAL_SERVER_ERROR);
		}

		ActionHooks::kirki_form_submitted($form_data, $form_config);

		return true;
	}

	/**
	 * Extract only the valid form field data from the request payload.
	 *
	 * Filters out internal submission metadata keys, ignores file-type fields
	 * (since file uploads are no longer supported), and strips any unconfigured keys.
	 *
	 * @param array $params The full request payload.
	 * @param array $fields Form field configuration.
	 * @return array The cleaned form data.
	 */
	protected function extract_form_data(array $params, array $fields = [])
	{
		$raw_data = $params;

		if (empty($fields)) {
			return $raw_data;
		}

		$form_data = [];

		foreach ($fields as $name => $field) {
			if (($field['type'] ?? null) === FormFieldTypes::FILE) {
				continue;
			}

			if (array_key_exists($name, $raw_data)) {
				$form_data[$name] = $raw_data[$name];
			}
		}

		return $form_data;
	}

	/**
	 * Parse and verify the base64 form metadata token.
	 *
	 * The token is signed with `wp_hash()` at render time, so an attacker
	 * cannot mint tokens for arbitrary form/post combinations.
	 *
	 * @param mixed $form_meta_data_base64 Base64 encoded form metadata.
	 * @return array{form_id: string|null, post_id: string|null}
	 */
	protected function parse_form_metadata($form_meta_data_base64)
	{
		if (!is_string($form_meta_data_base64)) {
			return ['form_id' => null, 'post_id' => null];
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$form_meta_data = explode('|', base64_decode(base64_decode($form_meta_data_base64)));

		if (count($form_meta_data) < 3) {
			return ['form_id' => null, 'post_id' => null];
		}

		$form_id = $form_meta_data[0];
		$post_id = $form_meta_data[1];
		$signature = $form_meta_data[2];

		$expected = wp_hash($form_id . '|' . $post_id);

		if (!hash_equals($expected, (string) $signature)) {
			return ['form_id' => null, 'post_id' => null];
		}

		return [
			'form_id' => $form_id ?: null,
			'post_id' => $post_id ?: null,
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
	 * @return array The validated form data.
	 *
	 * @throws ValidationException When validation fails.
	 */
	protected function validate_fields(array $form_data, array $fields)
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data = FormFieldRulesBuilder::data_for_validation($form_data, $fields);

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
