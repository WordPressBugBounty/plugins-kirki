<?php

/**
 * Define an inverse relation where the parent belongs to another model.
 * Constrains the related query to the owner record referenced by the parent's foreign key.
 * Supports lazy and eager loading and provides helpers to associate or dissociate the owner.
 *
 * @package    Framework
 * @subpackage Database\Query\Relations
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Query\Relations;

\defined('ABSPATH') || exit;
use Kirki\Framework\Database\Query\Collection;
use Kirki\Framework\Database\Concerns\HasDictionary;
use Kirki\Framework\Database\Query\Model;
use Kirki\Framework\Database\Query\QueryBuilder;
class BelongsTo extends Relation
{
    use HasDictionary;
    /**
     * The foreign key on the parent model that references the owner model.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $foreign_key;
    /**
     * The primary key or unique key on the related (owner) model being referenced.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $owner_key;
    /**
     * The child model instance.
     *
     * @var Model
     *
     * @since 1.0.0
     */
    protected $child;
    /**
     * The name of the relation.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $relation_name;
    /**
     * Create a new belongs-to relation instance.
     *
     * Stores the foreign and owner key names and applies constraints so the
     * query targets the owning record for the current parent model.
     *
     * @param Model $related The related (owner) model instance
     * @param Model $child The parent model instance
     * @param mixed $foreign_key The foreign key on the parent model
     * @param mixed $owner_key The key on the related model being referenced
     * @param string $relation_name The name of the relation
     *
     * @return void No return value
     *
     * @since 1.0.0
     */
    public function __construct(Model $related, Model $child, $foreign_key, $owner_key, $relation_name)
    {
        $this->foreign_key = $foreign_key;
        $this->owner_key = $owner_key;
        $this->relation_name = $relation_name;
        $this->child = $child;
        parent::__construct($related, $child);
    }
    /**
     * Apply base constraints to match the parent's foreign key.
     *
     * When the parent has a foreign key value, the query is scoped so that the
     * owner's key equals that value, ensuring only the referenced owner is
     * returned.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function add_constraints()
    {
        if (static::$constraints) {
            $key = $this->get_qualified_owner_key_name();
            $this->query->where($key, '=', $this->get_foreign_key_value_from($this->child));
        }
    }
    /**
     * Get the aggregate query for the relation.
     *
     * @param QueryBuilder $query The query builder instance
     * @param QueryBuilder $parent The parent query builder instance
     * @param mixed $columns The columns to select
     *
     * @return QueryBuilder The aggregate query
     *
     * @since 1.0.0
     */
    public function get_relation_existence_query(QueryBuilder $query, QueryBuilder $parent, $columns = ['*'])
    {
        if ($parent->from === $query->from) {
            return $this->get_relation_existence_query_for_self_relation($query, $parent, $columns);
        }
        return $query->select($columns)->where_column($this->get_qualified_foreign_key_name(), '=', $query->qualify_column($this->owner_key));
    }
    /**
     * Get the aggregate query for the relation for self join.
     *
     * @param QueryBuilder $query The query builder instance
     * @param QueryBuilder $parent The parent query builder instance
     * @param mixed $columns The columns to select
     *
     * @return QueryBuilder The aggregate query
     *
     * @since 1.0.0
     */
    public function get_relation_existence_query_for_self_relation(QueryBuilder $query, QueryBuilder $parent, $columns = ['*'])
    {
        $query->select($columns)->from($query->get_model()->get_table() . ' as ' . ($hash = $this->get_relation_count_hash()));
        $query->get_model()->set_table($hash);
        return $query->where_column($hash . '.' . $this->owner_key, '=', $this->get_qualified_foreign_key_name());
    }
    /**
     * Get the qualified foreign key name.
     *
     * @return string The qualified foreign key name
     *
     * @since 1.0.0
     */
    public function get_qualified_foreign_key_name()
    {
        return $this->child->prepare_column($this->foreign_key);
    }
    /**
     * Get the qualified owner key name.
     *
     * @return string The qualified owner key name
     *
     * @since 1.0.0
     */
    public function get_qualified_owner_key_name()
    {
        return $this->related->prepare_column($this->owner_key);
    }
    /**
     * Get the related owner model for lazy loading.
     *
     * Returns the first (and only) result from the constrained query.
     *
     * @return mixed The owner model instance or null if not found
     *
     * @since 1.0.0
     */
    public function get_results()
    {
        if (\is_null($this->get_foreign_key_value_from($this->child))) {
            return null;
        }
        return $this->first() ?? null;
    }
    /**
     * Add eager constraints across multiple parent models.
     *
     * Collects foreign key values from parents and scopes the query using a
     * where_in on the owner's key to fetch all owners in one query.
     *
     * @param Collection $models The parent models to derive keys from
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function add_eager_constraints(Collection $models)
    {
        $key = $this->get_qualified_owner_key_name();
        $this->where_in_eager($key, $this->get_eager_model_keys($models));
    }
    /**
     * Get the eager model keys.
     *
     * @param Collection $models The models.
     *
     * @return array The eager model keys.
     *
     * @since 1.0.0
     */
    protected function get_eager_model_keys(Collection $models)
    {
        $keys = [];
        foreach ($models as $model) {
            if (!\is_null($value = $this->get_foreign_key_value_from($model))) {
                $keys[] = $value;
            }
        }
        \sort($keys);
        return \array_values(\array_unique($keys));
    }
    /**
     * Get the foreign key from the model.
     *
     * @param Model $model The model.
     *
     * @return mixed The foreign key.
     *
     * @since 1.0.0
     */
    protected function get_foreign_key_value_from(Model $model)
    {
        return $model->{$this->foreign_key};
    }
    /**
     * Get the related key value from the model.
     *
     * @param Model $model The model.
     *
     * @return mixed The related key.
     *
     * @since 1.0.0
     */
    protected function get_related_key_value_from(Model $model)
    {
        return $model->{$this->owner_key};
    }
    /**
     * @inheritDoc
     */
    public function init_relation(Collection $models, $relation)
    {
        foreach ($models as $model) {
            $model->set_relation($relation, null);
        }
        return $models;
    }
    /**
     * Match eager loaded owners back to their parents.
     *
     * Builds a dictionary by owner key and assigns the corresponding owner
     * model to each parent under the relation name.
     *
     * @param Collection $models The parent models receiving owners
     * @param Collection $results The results.
     * @param string $relation The relation name on the parent
     *
     * @return Collection The parent models with owners assigned
     *
     * @since 1.0.0
     */
    public function match(Collection $models, Collection $results, $relation)
    {
        $dictionary = $this->build_dictionary($results);
        foreach ($models as $model) {
            $attribute = $this->get_dictionary_key($this->get_foreign_key_value_from($model));
            if ($attribute !== null && isset($dictionary[$attribute])) {
                $model->set_relation($relation, $dictionary[$attribute]);
            }
        }
        return $models;
    }
    /**
     * Build the dictionary for the relation from the results.
     *
     * @param Collection $results The results.
     *
     * @return array The dictionary
     *
     * @since 1.0.0
     */
    protected function build_dictionary(Collection $results)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $attribute = $this->get_dictionary_key($this->get_related_key_value_from($result));
            if ($attribute !== null) {
                $dictionary[$attribute] = $result;
            }
        }
        return $dictionary;
    }
    /**
     * Associate the parent with the given owner model.
     *
     * Sets the parent's foreign key to the owner's key value and updates the
     * in-memory relation reference for immediate access and serialization.
     *
     * @param Model|int|string|null $model The owner model to associate
     *
     * @return Model The parent model for method chaining
     *
     * @since 1.0.0
     */
    public function associate($model)
    {
        $owner_key = $model instanceof Model ? $model->get_attribute($this->owner_key) : $model;
        $this->child->set_attribute($this->foreign_key, $owner_key);
        if ($model instanceof Model) {
            $this->child->set_relation($this->relation_name, $model);
        } else {
            $this->child->unset_relation($this->relation_name);
        }
        return $this->child;
    }
    /**
     * Dissociate the parent from its current owner.
     *
     * Nulls the parent's foreign key and removes the in-memory relation entry
     * so that subsequent access reflects the dissociation.
     *
     * @return Model The parent model for method chaining
     *
     * @since 1.0.0
     */
    public function dissociate()
    {
        $this->child->set_attribute($this->foreign_key, null);
        return $this->child->set_relation($this->relation_name, null);
    }
    /**
     * Get the local key for the relation.
     *
     * Returns the key on the parent model that is used to match related records.
     *
     * @return string The local key name
     *
     * @since 1.0.0
     */
    public function get_local_key()
    {
        return $this->owner_key;
    }
    /**
     * Get the name of the relation.
     *
     * @return string The name of the relation
     *
     * @since 1.0.0
     */
    public function get_relation_name()
    {
        return $this->relation_name;
    }
}
