<?php

return [
    'validator' => [
        'failed' => __('Validation failed!', 'kirki'),
        /* translators: %s: Field name. */
        'invalid' => __('The %s field is invalid.', 'kirki'),
        /* translators: %s: Validation rule name. */
        'invalid_rule' => __('There is no validation rule defined as %s.', 'kirki'),
        'required' => [
            'default' => __('The {name} field is required.', 'kirki'),
        ],
        'required_if' => [
            'default' => __('The {name} field is required.', 'kirki'),
        ],
        'required_unless' => [
            'default' => __('The {name} field is required.', 'kirki'),
        ],
        'prohibited' => [
            'default' => __('The {name} field is prohibited.', 'kirki'),
        ],
        'prohibited_if' => [
            'default' => __('The {name} field is prohibited.', 'kirki'),
        ],
        'prohibited_unless' => [
            'default' => __('The {name} field is prohibited.', 'kirki'),
        ],
        'boolean' => [
            'default' => __('The {name} field must be a boolean.', 'kirki'),
        ],
        'object' => [
            'default' => __('The {name} field must be an object.', 'kirki'),
        ],
        'exists' => [
            'default' => __('The selected {name} does not exist.', 'kirki'),
        ],
        'unique' => [
            'default' => __('The {name} has already been taken.', 'kirki'),
        ],
        'in' => [
            'default' => __('The {name} field must be in the list of "{args}".', 'kirki'),
        ],
        'not_in' => [
            'default' => __('The {name} field must not be in the list of "{args}".', 'kirki'),
        ],
        'same_as' => [
            'default' => __('The {name} field must match the {args} field.', 'kirki'),
        ],
        'string' => [
            'min' => __('The {name} field must be at least {min} characters long.', 'kirki'),
            'max' => __('The {name} field must be at most {max} characters long.', 'kirki'),
            'length' => __('The {name} field must be {length} characters long.', 'kirki'),
            'size' => __('The {name} field must be {size} characters long.', 'kirki'),
            'email' => __('The {name} field must be a valid email address.', 'kirki'),
            'url' => __('The {name} field must be a valid url.', 'kirki'),
            'regex' => __('The {name} field format is invalid.', 'kirki'),
            'ip' => __('The {name} field must be a valid ip address.', 'kirki'),
            'alpha' => __('The {name} field must only contain alphabetic characters.', 'kirki'),
            'alpha_num' => __('The {name} field must only contain alphabetic characters and numbers.', 'kirki'),
            'alpha_dash' => __('The {name} field must only contain alphabetic characters, dashes and underscores.', 'kirki'),
            'between' => __('The {name} field must be between {min} and {max} characters long.', 'kirki'),
            'starts_with' => __('The {name} field must start with {starts_with}.', 'kirki'),
            'ends_with' => __('The {name} field must end with {ends_with}.', 'kirki'),
            'doesnt_start_with' => __('The {name} field must not start with {doesnt_start_with}.', 'kirki'),
            'doesnt_end_with' => __('The {name} field must not end with {doesnt_end_with}.', 'kirki'),
            'exactly' => __('The {name} field must be exactly {exactly} characters long.', 'kirki'),
            'default' => __('The {name} field must be a string.', 'kirki'),
        ],
        'array' => [
            'default' => __('The field {name} must be an array.', 'kirki'),
            'min' => __('The field {name} must have at least {min} items.', 'kirki'),
            'max' => __('The field {name} must have at most {max} items.', 'kirki'),
            'size' => __('The field {name} must have exactly {size} items.', 'kirki'),
            'exactly' => __('The field {name} must have exactly {exactly} items.', 'kirki'),
            'contains' => __('The field {name} must contain {contains}.', 'kirki'),
        ],
        'numeric' => [
            'default' => __('The field {name} must be a number.', 'kirki'),
            'min' => __('The field {name} must be greater than or equal to {min}.', 'kirki'),
            'max' => __('The field {name} must be less than or equal to {max}.', 'kirki'),
            'integer' => __('The field {name} must be an integer.', 'kirki'),
            'int' => __('The field {name} must be an integer.', 'kirki'),
            'float' => __('The field {name} must be a float.', 'kirki'),
            'decimal' => __('The field {name} must be a decimal.', 'kirki'),
            'gt' => __('The field {name} must be greater than {gt}.', 'kirki'),
            'gte' => __('The field {name} must be greater than or equal to {gte}.', 'kirki'),
            'lt' => __('The field {name} must be less than {lt}.', 'kirki'),
            'lte' => __('The field {name} must be less than or equal to {lte}.', 'kirki'),
        ],
        'date' => [
            'default' => __('The {name} field must be a valid date.', 'kirki'),
            'after' => __('The {name} field must be a date after {after}.', 'kirki'),
            'format' => __('The {name} field must match the format {format}.', 'kirki'),
            'datetime' => __('The {name} field must be a valid datetime.', 'kirki'),
        ],
        'file' => [
            'default' => __('The {name} field must be a file.', 'kirki'),
            'size' => __('The {name} field must be less than {size} kilobytes.', 'kirki'),
            'min' => __('The {name} field must be at least {min} kilobytes.', 'kirki'),
            'max' => __('The {name} field must be less than {max} kilobytes.', 'kirki'),
            'between' => __('The {name} field must be between {min} and {max} kilobytes.', 'kirki'),
            'image' => __('The {name} field must be an image.', 'kirki'),
            'types' => __('The {name} field must be a file of type: {types}.', 'kirki'),
            'extensions' => __('The {name} field must have one of the following extensions: {extensions}.', 'kirki'),
        ],
    ],
    'auth' => [
        'logged_in_required' => __('You have to be logged in.', 'kirki'),
        'admin_required' => __('You have to be logged in and have admin privileges.', 'kirki'),
        'unauthorized_request' => __('You are not authorized to make this request.', 'kirki'),
        /* translators: %s: Ability or action name. */
        'unauthorized_action' => __('You are not authorized to %s this resource.', 'kirki'),
        'no_policy' => __('No policy found for this resource. Pass the model instance or class name as the second argument.', 'kirki'),
        /* translators: %s: Ability name. */
        'ability_not_defined' => __('The ability %s is not defined in the policy for this resource.', 'kirki'),
        'upload_forbidden' => __('You are not authorized to upload files.', 'kirki'),
        'invalid_upload_path' => __('Invalid upload path.', 'kirki'),
    ],
    'model' => [
        /* translators: 1: Model class name, 2: Comma-separated list of ids. */
        'not_found' => __('No query results for model [%s] with ids: %s', 'kirki'),
    ],
    'filesystem' => [
        /* translators: %s: File path. */
        'file_not_found' => __('file does not exists at [%s]', 'kirki'),
    ],
    'upload' => [
        'directory_unavailable' => __('Upload directory is not available.', 'kirki'),
        /* translators: 1: Source file path, 2: Destination file path, 3: Error message. */
        'move_failed' => __('Could not move the file "%s" to "%s" (%s).', 'kirki'),
        /* translators: 1: File name, 2: Maximum allowed file size in KiB. */
        'ini_size_exceeded' => __('The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).', 'kirki'),
        /* translators: %s: File name. */
        'form_size_exceeded' => __('The file "%s" exceeds the upload limit defined in your form.', 'kirki'),
        /* translators: %s: File name. */
        'partial' => __('The file "%s" was only partially uploaded.', 'kirki'),
        'no_file' => __('No file was uploaded.', 'kirki'),
        /* translators: %s: File name. */
        'cant_write' => __('The file "%s" could not be written on disk.', 'kirki'),
        'no_tmp_dir' => __('File could not be uploaded: missing temporary directory.', 'kirki'),
        'stopped_by_extension' => __('File upload was stopped by a PHP extension.', 'kirki'),
        /* translators: %s: File name. */
        'unknown_error' => __('The file "%s" was not uploaded due to an unknown error.', 'kirki'),
    ],
];