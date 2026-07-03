<?php

return [
    // translators: %s: Field name.
    'validator.invalid' => __('The value provided for %s is invalid.', 'kirki'),
    // translators: %s: Field name.
    'validator.required' => __('The %s field is required .', 'kirki'),
    // translators: %s: Field name.
    'validator.string' => __('The %s field must be of type string.', 'kirki'),
    // translators: %s: Field name.
    'validator.array' => __('The %s field must be of type array.', 'kirki'),
    // translators: %s: Field name.
    'validator.object' => __('The %s field must be of type object.', 'kirki'),
    // translators: %s: Field name.
    'validator.boolean' => __('The %s field must be of type boolean.', 'kirki'),
    // translators: %s: Field name.
    'validator.integer' => __('The %s field must be of type integer.', 'kirki'),
    // translators: %s: Field name.
    'validator.number' => __('The %s field must be of type number.', 'kirki'),
    // translators: %s: Field name.
    'validator.float' => __('The %s field must be of type float.', 'kirki'),
    // translators: %s: Field name.
    'validator.email' => __('The %s field must be of type email.', 'kirki'),
    // translators: %s: Email address.
    'validator.email_unique' => __('The email address %s is already in use.', 'kirki'),
    // translators: %s: Field name.
    'validator.unique' => __('The value for %s must be unique.', 'kirki'),
    // translators: %s: Field name.
    'validator.url' => __('The %s field must be of type url.', 'kirki'),
    'validator.exists' => __('Resource does not exist.', 'kirki'),
    // translators: 1: Field name, 2: Character count.
    'validator.min.characters' => __('The %s field must be greater than or equal to %s characters.', 'kirki'),
    // translators: 1: Field name, 2: Item count.
    'validator.min.items' => __('The %s field must be greater than or equal to %s items.', 'kirki'),
    // translators: 1: Field name, 2: Minimum value.
    'validator.min.numeric' => __('The %s field must be greater than or equal %s.', 'kirki'),
    // translators: 1: Field name, 2: Character count.
    'validator.max.characters' => __('The %s field must be less than or equal to %s characters.', 'kirki'),
    // translators: 1: Field name, 2: Item count.
    'validator.max.items' => __('The %s field must be less than or equal to %s items.', 'kirki'),
    // translators: 1: Field name, 2: Maximum value.
    'validator.max.numeric' => __('The %s field must be less than or equal %s.', 'kirki'),
    // translators: 1: Field name, 2: Allowed values list.
    'validator.in' => __('The %s field must contain a value from: %s.', 'kirki'),
    // translators: 1: Field name, 2: Disallowed values list.
    'validator.not_in' => __('The %s field must not contain a value from: %s.', 'kirki'),
    // translators: 1: Field name, 2: Regular expression.
    'validator.regex' => __('The %s field must match the regex: %s.', 'kirki'),
    // translators: %s: Field name.
    'validator.sanitize' => __('The %s field must be sanitized.', 'kirki'),
    // translators: 1: Field name, 2: Sibling field name.
    'validator.same_as' => __('The %s field must be same as %s.', 'kirki'),
    // translators: 1: Field name, 2: Date format.
    'validator.date' => __('The %s field must be a valid date time in the format %s.', 'kirki'),
    // translators: 1: Field name, 2: Date format.
    'validator.datetime' => __('The %s field must be a valid date time in the format %s.', 'kirki'),
    // translators: 1: Field name, 2: Date format.
    'validator.date_format' => __('The %s field must be a valid date in the format %s.', 'kirki'),
    // translators: %s: Field name.
    'validator.is_valid_image_id' => __('The %s field must be a valid media image', 'kirki'),
    // translators: %s: Field name.
    'validator.required_if' => __('The %s field is required.', 'kirki'),
    // translators: %s: Field name.
    'validator.required_if_sibling' => __('The %s field is required.', 'kirki'),
    // translators: %s: Field name.
    'validator.prohibited' => __('The %s field is prohibited.', 'kirki'),
    // translators: %s: Field name.
    'validator.prohibited_if' => __('The %s field is prohibited.', 'kirki'),
    // translators: %s: Field name.
    'validator.required_if_exists' => __('The %s field is required.', 'kirki'),
    // translators: %s: User ID.
    'validator.user_exists' => __('User with id %s does not exist.', 'kirki'),
    // translators: 1: Field name, 2: Date or field name.
    'validator.after' => __('The %s field must be after %s.', 'kirki'),
    // translators: 1: Field name, 2: Compare value.
    'validator.gt' => __('The %s field must be greater than %s.', 'kirki'),
    // translators: 1: Field name, 2: Compare value.
    'validator.gte' => __('The %s field must be greater than %s.', 'kirki'),
    // translators: 1: Field name, 2: Compare value.
    'validator.lt' => __('The %s field must be greater than %s.', 'kirki'),
    // translators: 1: Field name, 2: Compare value.
    'validator.lte' => __('The %s field must be greater than %s.', 'kirki'),
    'validator.failed' => __('Validation failed!', 'kirki'),
    // translators: %s: Field name or path.
    'validator.expected_array' => __('Expected an array at "%s"', 'kirki'),
    'auth.logged_in_required' => __('You have to be logged in.', 'kirki'),
    'auth.admin_required' => __('You have to be logged in and have admin privileges.', 'kirki'),
    'auth.unauthorized_request' => __('You are not authorized to make this request.', 'kirki'),
    // translators: %s: Action name.
    'auth.unauthorized_action' => __('You are not authorized to %s this resource.', 'kirki'),
    'auth.no_policy' => __('No policy found for this resource.', 'kirki'),
    // translators: %s: Ability name.
    'auth.ability_not_defined' => __('The ability %s is not defined in the policy for this resource.', 'kirki'),
    'auth.upload_forbidden' => __('You are not authorized to upload files.', 'kirki'),
    'auth.invalid_upload_path' => __('Invalid upload path.', 'kirki'),
    // translators: 1: Model class name, 2: Model IDs list.
    'model.not_found' => __('No query results for model [%s] with ids: %s', 'kirki'),
    // translators: %s: File path.
    'filesystem.file_not_found' => __('file does not exists at [%s]', 'kirki'),
    'upload.directory_unavailable' => __('Upload directory is not available.', 'kirki'),
    // translators: 1: File name, 2: Destination path, 3: Error message.
    'upload.move_failed' => __('Could not move the file "%s" to "%s" (%s).', 'kirki'),
    // translators: 1: File name, 2: Limit size in KiB.
    'upload.ini_size_exceeded' => __('The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).', 'kirki'),
    // translators: %s: File name.
    'upload.form_size_exceeded' => __('The file "%s" exceeds the upload limit defined in your form.', 'kirki'),
    // translators: %s: File name.
    'upload.partial' => __('The file "%s" was only partially uploaded.', 'kirki'),
    'upload.no_file' => __('No file was uploaded.', 'kirki'),
    // translators: %s: File name.
    'upload.cant_write' => __('The file "%s" could not be written on disk.', 'kirki'),
    'upload.no_tmp_dir' => __('File could not be uploaded: missing temporary directory.', 'kirki'),
    'upload.stopped_by_extension' => __('File upload was stopped by a PHP extension.', 'kirki'),
    // translators: %s: File name.
    'upload.unknown_error' => __('The file "%s" was not uploaded due to an unknown error.', 'kirki')
];