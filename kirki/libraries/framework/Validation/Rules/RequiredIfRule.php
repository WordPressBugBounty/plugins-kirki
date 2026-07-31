<?php

/**
 * Required rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Closure;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Validation\ValidationRule;
use InvalidArgumentException;
use function Kirki\Framework\deep_get;
use function Kirki\Framework\Polyfill\array_first;
\defined('ABSPATH') || exit;
class RequiredIfRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'required_if';
    /**
     * Whether the rule is an implicit rule.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    public bool $is_implicit = \true;
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        $callback = $this->get_callback();
        if (!$callback()) {
            return \true;
        }
        if (static::is_nullish($this->value)) {
            return $this->fails($this->default_messages['default']);
        }
        return \true;
    }
    /**
     * Get the callback.
     *
     * @return Closure
     *
     * @since 1.0.0
     */
    protected function get_callback()
    {
        $arguments = Arr::wrap($this->args);
        $other = array_first($arguments);
        $value = \array_slice($arguments, 1);
        if (!$other instanceof Closure) {
            $other = function () use($other, $value) {
                if (empty($value)) {
                    throw new InvalidArgumentException('The second argument must be a non-empty string.');
                }
                $data = deep_get($this->data, (string) $other);
                if (\count($value) > 1) {
                    return \in_array($data, $value);
                }
                return $data == array_first($value);
            };
        }
        return $other;
    }
    // other => true
    // other,1
    /**
     * Get the error message.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function messages()
    {
        return $this->process_messages($this->messages);
    }
}
