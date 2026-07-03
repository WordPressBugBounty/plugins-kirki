<?php

/**
 * Eloquent collection specialized for model instances with dictionary-based merge.
 * Indexes items by primary key to deduplicate during merge operations.
 * Extends the base Collection with model-aware collection behavior.
 *
 * @package    Framework
 * @subpackage Database\Query
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Query;

\defined('ABSPATH') || exit;
use Kirki\Framework\Collections\Collection as BaseCollection;
use Kirki\Framework\Database\Concerns\HasDictionary;
use Override;
use function Kirki\Framework\value;
class Collection extends BaseCollection
{
    use HasDictionary;
    /**
     * Merge the items into the collection.
     *
     * @param mixed $items The items.
     *
     * @return static The merged collection
     *
     * @since 1.0.0
     */
    public function merge($items)
    {
        $dictionary = $this->get_dictionary();
        foreach ($items as $item) {
            $key = $this->get_dictionary_key($item->get_primary_key_value());
            if ($key !== null) {
                $dictionary[$key] = $item;
            }
        }
        return new static(\array_values($dictionary));
    }
    /**
     * Get the dictionary for the collection.
     *
     * @param mixed $items The items.
     *
     * @return array The dictionary
     *
     * @since 1.0.0
     */
    public function get_dictionary($items = null)
    {
        $items = \is_null($items) ? $this->items : $items;
        $dictionary = [];
        foreach ($items as $item) {
            $key = $this->get_dictionary_key($item->get_primary_key_value());
            if ($key !== null) {
                $dictionary[$key] = $item;
            }
        }
        return $dictionary;
    }
    /**
     * Eager load the relationships for the collection.
     *
     * @param mixed $relations The relations to eager load
     *
     * @return static A new collection containing the eager loaded items
     *
     * @since 1.0.0
     */
    public function load($relations)
    {
        if ($this->not_empty()) {
            if (\is_string($relations)) {
                $relations = \func_get_args();
            }
            $query = $this->first()->new_query_without_relations()->with($relations);
            $this->items = $query->eager_load_relations($this->new_instance($this->items));
        }
        return $this;
    }
    /**
     * Eager load the missing relationships for the collection.
     *
     * @param mixed $relations The relations to eager load
     *
     * @return static A new collection containing the eager loaded items
     *
     * @since 1.0.0
     */
    public function load_missing($relations)
    {
        if (\is_string($relations)) {
            $relations = \func_get_args();
        }
        if ($this->not_empty()) {
            $query = $this->first()->new_query_without_relations()->with($relations);
            $with_relations = $query->get_with_relations();
            foreach ($with_relations as $key => $value) {
                $path = $this->flatten_relations($key, $value);
                $this->load_missing_relation($this, $path);
            }
        }
    }
    /**
     * Load the missing relation.
     *
     * @param self $models The models
     * @param array $path The path
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function load_missing_relation(self $models, array $path)
    {
        $relation = \array_shift($path);
        $name = \key($relation);
        if (\is_string(\reset($relation))) {
            $relation = \reset($relation);
        }
        $models->filter(fn($model) => !\is_null($model) && !$model->relation_loaded($name))->load($relation);
        if (empty($path)) {
            return;
        }
        $models = $models->pluck($name)->filter();
        if ($models->first() instanceof BaseCollection) {
            $models = $models->collapse();
        }
        $this->load_missing_relation(new static($models), $path);
    }
    /**
     * Flatten the relations.
     *
     * @param array $relations The relations to flatten
     *
     * @return array The flattened relations
     *
     * @since 1.0.0
     */
    protected function flatten_relations($parent, array $relations)
    {
        $result ??= [];
        if (\is_string($parent)) {
            $result = \array_merge($result, [[$parent => $parent]]);
        }
        if (empty($relations)) {
            return $result;
        }
        foreach ($relations as $key => $value) {
            if (\is_callable($value)) {
                $result[\count($result) - 1][\key($result[\count($result) - 1])] = $value;
                continue;
            }
            $result = \array_merge($result, $this->flatten_relations($key, $value));
        }
        return $result;
    }
    /**
     * Get the primary keys of the collection.
     *
     * @return array The primary keys
     *
     * @since 1.0.0
     */
    public function model_keys()
    {
        return \array_map(fn($model) => $model->get_primary_key_value(), $this->items);
    }
    /**
     * Map the collection items using a callback.
     *
     * @param callable $callback The callback to map the items
     *
     * @return Collection The mapped collection
     *
     * @since 1.0.0
     */
    public function map(callable $callback)
    {
        $result = parent::map($callback);
        return $result->contains(fn($item) => !$item instanceof Model) ? $result->to_base() : $result;
    }
    /**
     * Get the unique items from the collection.
     *
     * @param callable|string|null $key The key to use for uniqueness.
     * @param bool $strict Whether to use strict comparison.
     *
     * @return static A new collection containing the unique items.
     *
     * @since 1.0.0
     */
    public function unique($key = null, $strict = \false)
    {
        if (!\is_null($key)) {
            return parent::unique($key, $strict);
        }
        return new static(\array_values($this->get_dictionary()));
    }
}
