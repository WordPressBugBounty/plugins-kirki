<?php

/**
 * Eager load one or more relations for a set of models.
 * Accept an array of models and a list of relation method names,
 * then fetch and match related records in as few queries as possible.
 * This reduces N+1 query patterns by batching lookups and hydrating relation data onto the provided models.
 *
 * @package    Framework
 * @subpackage Database\Query
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Query;

\defined('ABSPATH') || exit;
use Closure;
use Kirki\Framework\Database\Query\Collection;
use Kirki\Framework\Database\Concerns\HasDictionary;
use Kirki\Framework\Database\Query\Relations\Relation;
use function Kirki\Framework\Polyfill\array_first;
class EagerLoader
{
    use HasDictionary;
    /**
     * The array of model instances that will have their relations eagerly loaded.
     *
     * @var Collection
     *
     * @since 1.0.0
     */
    protected $models;
    /**
     * The list of relation method names to be eager loaded for the provided models.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $relations;
    /**
     * Create a new eager loader instance.
     *
     * Stores the models and the requested relation names for later processing.
     * Instances are typically constructed by the query builder when handling
     * with() calls to prefetch related data efficiently.
     *
     * @param Collection $models The models that will receive related data
     * @param array $relations The relation method names to eager load
     *
     * @return void No return value
     *
     * @since 1.0.0
     */
    public function __construct(Collection $models, array $relations)
    {
        $this->models = $models;
        $this->relations = $relations;
    }
    /**
     * Execute eager loading for all requested relations.
     *
     * Iterates over the configured relation names and invokes the loader for
     * each. Returns the models array with relations populated on each model
     * instance under their respective relation keys.
     *
     * @return array The array of models with loaded relations
     *
     * @since 1.0.0
     */
    public function load()
    {
        foreach ($this->relations as $relation_name => $nested_relations) {
            $this->load_relation($relation_name, $nested_relations);
        }
        return $this->models;
    }
    /**
     * Load a single relation across all provided models.
     *
     * Determines if the relation method exists, derives the relation object,
     * applies eager constraints, fetches related results, and matches them to
     * their parents. No operation occurs when models are empty or the method
     * is missing.
     *
     * @param string $relation_name The relation method name to load
     * @param mixed $nested_relations Nested relations to load on the related models
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function load_relation($relation_name, $nested_relations = null)
    {
        if ($this->models->empty()) {
            return;
        }
        $first_model = $this->models->first();
        if (!\method_exists($first_model, $relation_name)) {
            return;
        }
        /**
         * The relation instance.
         *
         * @var Relation
         */
        $relation = $first_model->{$relation_name}();
        $relation->add_eager_constraints($this->models);
        $callback = array_first($nested_relations);
        if ($callback instanceof Closure) {
            $callback($relation->get_query());
        }
        $results = $relation->get_eager();
        $this->models = $relation->match($relation->init_relation($this->models, $relation_name), $results, $relation_name);
        if (!empty($nested_relations) && !$results->empty() && !$callback instanceof Closure) {
            $this->load_nested_relations($relation_name, $nested_relations);
        }
    }
    /**
     * Load nested relations on the related models.
     *
     * @param string $relation_name The parent relation name
     * @param array $nested_relations The nested relations to load
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function load_nested_relations($relation_name, $nested_relations)
    {
        $related_models = new Collection();
        foreach ($this->models as $model) {
            if (!isset($model->relations[$relation_name])) {
                continue;
            }
            $related = $model->relations[$relation_name];
            if ($related instanceof Collection) {
                $related_models = $related_models->merge($related);
            } elseif (\is_object($related)) {
                $related_models->push($related);
            }
        }
        if (!empty($related_models)) {
            (new static($related_models, $nested_relations))->load();
        }
    }
}
