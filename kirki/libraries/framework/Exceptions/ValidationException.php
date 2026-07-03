<?php

/**
 * Exception thrown when validation fails.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Http\Response;
use Exception;
use function Kirki\Framework\message;
class ValidationException extends Exception
{
    /**
     * The errors.
     *
     * @var array<string>
     *
     * @since 1.0.0
     */
    protected $errors;
    /**
     * With errors.
     *
     * @param array $errors The errors.
     * @param string $message The message.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function with_errors(array $errors, string $message = '')
    {
        if ($message === '') {
            $message = message('validator.failed');
        }
        $code = Response::UNPROCESSABLE_ENTITY;
        $instance = new static($message, $code);
        $instance->errors = $errors;
        return $instance;
    }
    /**
     * Get the errors.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_errors()
    {
        return $this->errors;
    }
}
