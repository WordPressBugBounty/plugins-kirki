<?php

/**
 * Exception thrown when a model (database record) is not found.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Arr;
use function Kirki\Framework\message;
class ModelNotFoundException extends NotFoundException
{
    /**
     * The model class name being queried.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $model;
    /**
     * The IDs or route keys that were searched for.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $ids;
    /**
     * Create a new ModelNotFoundException instance.
     *
     * @param string $model The model class name.
     * @param mixed $ids The ID(s) or route key(s) searched for.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = $ids;
        $this->prepare_message();
    }
    /**
     * Set the model class name for the exception.
     *
     * @param string $model The model class name.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function set_model($model)
    {
        $this->model = $model;
        $this->prepare_message();
    }
    /**
     * Set the IDs or route keys that were searched for.
     *
     * @param mixed $ids The ID(s) or route key(s).
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function set_ids($ids)
    {
        $this->ids = $ids;
        $this->prepare_message();
    }
    /**
     * Get the model class name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_model()
    {
        return $this->model;
    }
    /**
     * Get the ID(s) or route key(s).
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_ids()
    {
        return $this->ids;
    }
    /**
     * Prepare the exception message using the model and ids.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare_message()
    {
        $ids = Arr::wrap($this->ids);
        $this->message = message('model.not_found', $this->model, \implode(', ', $ids));
    }
}
