<?php

/**
 * The messages bag class.
 *
 * @package    Framework
 * @subpackage Supports
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports;

use function Kirki\Framework\app;
use function Kirki\Framework\config;
\defined('ABSPATH') || exit;
class MessagesBag
{
    /**
     * The messages bag where all the app defined user faced messages are stored.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $messages = [];
    /**
     * Create a new messages bag instance.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->messages = $this->messages();
    }
    /**
     * Get the default messages.
     * These values can be overridden by the app defined messages
     * at config/messages.php file.
     * Plugin developers can use translated message text there.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function defaults()
    {
        return [
            'validator.invalid' => 'The value provided for %s is invalid.',
            'validator.required' => 'The %s field is required.',
            'validator.string' => 'The %s field must be of type string.',
            'validator.array' => 'The %s field must be of type array.',
            'validator.object' => 'The %s field must be of type object.',
            'validator.boolean' => 'The %s field must be of type boolean.',
            'validator.integer' => 'The %s field must be of type integer.',
            'validator.number' => 'The %s field must be of type number.',
            'validator.float' => 'The %s field must be of type float.',
            'validator.email' => 'The %s field must be of type email.',
            'validator.email_unique' => 'The email address %s is already in use.',
            'validator.unique' => 'The value for %s must be unique.',
            'validator.url' => 'The %s field must be of type url.',
            'validator.exists' => 'Resource does not exist.',
            'validator.min.characters' => 'The %s field must be greater than or equal to %s characters.',
            'validator.min.items' => 'The %s field must be greater than or equal to %s items.',
            'validator.min.numeric' => 'The %s field must be greater than or equal %s.',
            'validator.max.characters' => 'The %s field must be less than or equal to %s characters.',
            'validator.max.items' => 'The %s field must be less than or equal to %s items.',
            'validator.max.numeric' => 'The %s field must be less than or equal %s.',
            'validator.in' => 'The %s field must contain a value from: %s.',
            'validator.not_in' => 'The %s field must not contain a value from: %s.',
            'validator.regex' => 'The %s field must match the regex: %s.',
            'validator.sanitize' => 'The %s field must be sanitized.',
            'validator.same_as' => 'The %s field must be same as %s.',
            'validator.date' => 'The %s field must be a valid date time in the format %s.',
            'validator.datetime' => 'The %s field must be a valid date time in the format %s.',
            'validator.date_format' => 'The %s field must be a valid date in the format %s.',
            'validator.is_valid_image_id' => 'The %s field must be a valid media image',
            'validator.required_if' => 'The %s field is required.',
            'validator.required_if_sibling' => 'The %s field is required.',
            'validator.prohibited' => 'The %s field is prohibited.',
            'validator.prohibited_if' => 'The %s field is prohibited.',
            'validator.required_if_exists' => 'The %s field is required.',
            'validator.user_exists' => 'User with id %s does not exist.',
            'validator.after' => 'The %s field must be after %s.',
            'validator.gt' => 'The %s field must be greater than %s.',
            'validator.gte' => 'The %s field must be greater than %s.',
            'validator.lt' => 'The %s field must be greater than %s.',
            'validator.lte' => 'The %s field must be greater than %s.',
            'validator.failed' => 'Validation failed!',
            'validator.expected_array' => 'Expected an array at "%s"',
            'auth.logged_in_required' => 'You have to be logged in.',
            'auth.admin_required' => 'You have to be logged in and have admin privileges.',
            'auth.unauthorized_request' => 'You are not authorized to make this request.',
            'auth.unauthorized_action' => 'You are not authorized to %s this resource.',
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'auth.no_policy' => 'No policy found for this resource. Pass the model instance or class name as the second argument.',
            'auth.ability_not_defined' => 'The ability %s is not defined in the policy for this resource.',
            'auth.upload_forbidden' => 'You are not authorized to upload files.',
            'auth.invalid_upload_path' => 'Invalid upload path.',
            'model.not_found' => 'No query results for model [%s] with ids: %s',
            'filesystem.file_not_found' => 'file does not exists at [%s]',
            'upload.directory_unavailable' => 'Upload directory is not available.',
            'upload.move_failed' => 'Could not move the file "%s" to "%s" (%s).',
            'upload.ini_size_exceeded' => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).',
            'upload.form_size_exceeded' => 'The file "%s" exceeds the upload limit defined in your form.',
            'upload.partial' => 'The file "%s" was only partially uploaded.',
            'upload.no_file' => 'No file was uploaded.',
            'upload.cant_write' => 'The file "%s" could not be written on disk.',
            'upload.no_tmp_dir' => 'File could not be uploaded: missing temporary directory.',
            'upload.stopped_by_extension' => 'File upload was stopped by a PHP extension.',
            'upload.unknown_error' => 'The file "%s" was not uploaded due to an unknown error.',
        ];
    }
    /**
     * Get the app defined messages.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function app_defined_messages()
    {
        $app = app();
        if (!\method_exists($app, 'config_path')) {
            return [];
        }
        return config('messages', []);
    }
    /**
     * Get the messages.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function messages()
    {
        return \array_merge($this->defaults(), $this->app_defined_messages());
    }
    /**
     * Get a message by key.
     *
     * @param string $key The key.
     * @param mixed $args The arguments.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get(string $key, ...$args)
    {
        if (!\array_key_exists($key, $this->messages)) {
            return null;
        }
        $args = !empty($args) ? Arr::flatten($args) : [];
        return \vsprintf(esc_html($this->messages[$key]), $args);
    }
}
