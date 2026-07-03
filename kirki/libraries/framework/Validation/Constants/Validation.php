<?php

/**
 * Maps validation rule string names to their rule class implementations.
 * Serves as the central registry consumed by the Validator to resolve rule tokens like required, email, and unique.
 * Keeps rule discovery declarative and extensible.
 *
 * @package    Framework
 * @subpackage Validation\Constants
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Constants;

\defined('ABSPATH') || exit;
use Kirki\Framework\Validation\Rules\AfterRule;
use Kirki\Framework\Validation\Rules\ArrayRule;
use Kirki\Framework\Validation\Rules\BooleanRule;
use Kirki\Framework\Validation\Rules\DateFormatRule;
use Kirki\Framework\Validation\Rules\DateRule;
use Kirki\Framework\Validation\Rules\DateTimeRule;
use Kirki\Framework\Validation\Rules\EmailRule;
use Kirki\Framework\Validation\Rules\EmailUniqueRule;
use Kirki\Framework\Validation\Rules\ExistsRule;
use Kirki\Framework\Validation\Rules\FloatRule;
use Kirki\Framework\Validation\Rules\GreaterThanEqualRule;
use Kirki\Framework\Validation\Rules\GreaterThanRule;
use Kirki\Framework\Validation\Rules\InRule;
use Kirki\Framework\Validation\Rules\IntegerRule;
use Kirki\Framework\Validation\Rules\IsValidImageIdRule;
use Kirki\Framework\Validation\Rules\LessThanEqualRule;
use Kirki\Framework\Validation\Rules\LessThanRule;
use Kirki\Framework\Validation\Rules\MaxRule;
use Kirki\Framework\Validation\Rules\MinRule;
use Kirki\Framework\Validation\Rules\NotInRule;
use Kirki\Framework\Validation\Rules\NullableRule;
use Kirki\Framework\Validation\Rules\NumberRule;
use Kirki\Framework\Validation\Rules\ObjectRule;
use Kirki\Framework\Validation\Rules\ProhibitedIfRule;
use Kirki\Framework\Validation\Rules\ProhibitedRule;
use Kirki\Framework\Validation\Rules\RegexRule;
use Kirki\Framework\Validation\Rules\RequiredIfExists;
use Kirki\Framework\Validation\Rules\RequiredIfRule;
use Kirki\Framework\Validation\Rules\RequiredIfSiblingRule;
use Kirki\Framework\Validation\Rules\RequiredRule;
use Kirki\Framework\Validation\Rules\SameAsRule;
use Kirki\Framework\Validation\Rules\Sanitizer;
use Kirki\Framework\Validation\Rules\StringRule;
use Kirki\Framework\Validation\Rules\UniqueRule;
use Kirki\Framework\Validation\Rules\UrlRule;
use Kirki\Framework\Validation\Rules\UserExists;
class Validation
{
    /**
     * The rules to validator method map
     * 
     * @var array
     * 
     * @since 3.3.0
     */
    public const RULE_MAP = ['required' => RequiredRule::class, 'string' => StringRule::class, 'array' => ArrayRule::class, 'object' => ObjectRule::class, 'boolean' => BooleanRule::class, 'integer' => IntegerRule::class, 'number' => NumberRule::class, 'float' => FloatRule::class, 'email' => EmailRule::class, 'email_unique' => EmailUniqueRule::class, 'unique' => UniqueRule::class, 'url' => UrlRule::class, 'exists' => ExistsRule::class, 'min' => MinRule::class, 'max' => MaxRule::class, 'in' => InRule::class, 'not_in' => NotInRule::class, 'regex' => RegexRule::class, 'sanitize' => Sanitizer::class, 'same_as' => SameAsRule::class, 'nullable' => NullableRule::class, 'date' => DateRule::class, 'datetime' => DateTimeRule::class, 'date_format' => DateFormatRule::class, 'is_valid_image_id' => IsValidImageIdRule::class, 'required_if' => RequiredIfRule::class, 'required_if_sibling' => RequiredIfSiblingRule::class, 'prohibited' => ProhibitedRule::class, 'prohibited_if' => ProhibitedIfRule::class, 'required_if_exists' => RequiredIfExists::class, 'user_exists' => UserExists::class, 'after' => AfterRule::class, 'gt' => GreaterThanRule::class, 'gte' => GreaterThanEqualRule::class, 'lt' => LessThanRule::class, 'lte' => LessThanEqualRule::class];
}
