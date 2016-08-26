<?php
namespace Uri\Template;

use \Uri\Lexical\CharacterTypes;
use \Uri\Template\Variables\Variable;

/**
 * Represents a URI template expression operator,
 *
 * Each operator has a number of different attributes associated with it. They
 * are
 *
 *   - A prefix. If any variables in an expression are defined, then the prefix
 *     inserted in front of the expression's expansion.
 *
 *   - A separator. A string to insert in between the expansions of defined
 *     variables in the expression.
 *
 *   - Whether named parameters are expanded. If so, string-valued variables are
 *     expanded as though the variable name were a key associated with the
 *     value. Similarly, unexploded lists and associated arrays are first
 *     expanded, then inserted into the result with the variable name rendered
 *     though it were a key for the result string. Also, when exploding lists,
 *     the variable name is used as a key for each value in the list. This
 *     attribute has no effect on expanding associative arrays, since a key
 *     already exists for each value in the array.
 *
 *   - Whether form-style parameters are required. When rendering a key/value
 *     pair, it is sometimes permissible to elide the "=" in case the value is
 *     empty. This omission will occur if and only if form-style parameters are
 *     *not* required. Note that this does not effect the case of an undefined
 *     (`null`) value, since such values are implicitly ignored during
 *     expansion.
 *
 *   - The permitted character set for expansions. Any character in a value
 *     which is not in the permitted character set is percent encoded. All
 *     operators permit characters in the "unreserved" set.
 *
 * See [RFC-6570 §1.5](https://tools.ietf.org/html/rfc6570#section-1.5) for the
 * definitions of various character sets.
 */
class Operator {
	/**
	 * The character defined character sets.
	 *
	 * @var CharacterTypes
	 */
	private $charTypes;

	/**
	 * The prefix to apply in front of expression expansions.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * The separator to insert between variables expansions.
	 *
	 * @var string
	 */
	private $separator;

	/**
	 * Whether to expand variable names.
	 *
	 * @var bool
	 */
	private $expandNamedParameters;

	/**
	 * Whether to require key/value pairs to be rendered as form-style
	 * parameters.
	 *
	 * @var bool
	 */
	private $requireFormStyleParameters;

	/**
	 * Whether to preserve percent-encoded charcters and characters in the
	 * "unreserved" set.
	 *
	 * @var bool
	 */
	private $permitSpecialCharacters;

	/**
	 * Initializes an `Operator` instance.
	 *
	 * @param CharacterTypes $charTypes
	 * The defined character sets. Must define at least the `'reserved'` and
	 * `'unreserved'` character sets.
	 *
	 * @param string $prefix
	 * The prefix to apply in front of expression expansions.
	 *
	 * @param string $separator
	 * The string to insert in between variable expansions with an expression.
	 *
	 * @param bool $expandNamedParameters
	 * Whether named parameters are expanded for strings and list values.
	 *
	 * @param bool $requireFormStyleParameters
	 * Whether form-style parameters are required for this operator.
	 *
	 * @param bool $permitSpecialCharacters
	 * If `true`, percent-encoded characters and characters in the "reserved"
	 * set will be expanded without further encoding (this is in addition to the
	 * usual "unreserved" characters).
	 */
	public function __construct(CharacterTypes $charTypes, $prefix, $separator, $expandNamedParameters, $requireFormStyleParameters, $permitSpecialCharacters) {
		$this->charTypes = $charTypes;
		$this->prefix = (string)$prefix;
		$this->separator = (string)$separator;
		$this->expandNamedParameters = (bool)$expandNamedParameters;
		$this->requireFormStyleParameters = (bool)$requireFormStyleParameters;
		$this->permitSpecialCharacters = $permitSpecialCharacters;
	}

	/**
	 * Determines which key to associate with the variable's value during
	 * expansion.
	 *
	 * @param string $name
	 * The variable name which will be expanded.
	 *
	 * @return string|null
	 * If a key is to be used for the variable, then that key is returned.
	 * Otherwise, `null` is returned to indicate that no key should be used.
	 */
	public function chooseDefaultKey($name) {
		if ($this->expandNamedParameters) {
			return $name;
		}

		return null;
	}

	/**
	 * Combines the results of variables expansions into a single result for an
	 * expression.
	 *
	 * If there are no non-`null` values, then the result is the empty string.
	 * Otherwise the result consists of the operator's prefix following by the
	 * joining of the values, separated by the operator's separator.
	 *
	 * @param string[] $parts
	 * The values produced by expanding each variable of an expression in order.
	 *
	 * @return string
	 * The combination of all the values.
	 *
	 * @todo
	 * Pluralize this to `combineValues`, and renamed `$parts` to `$values`.
	 */
	public function combineValue(array $parts) {
		// Don't use `null` parts (empty parts must be kept, though).
		$parts = \array_filter(
			$parts,
			static function ($part) {
				return !\is_null($part);
			}
		);

		if (empty($parts)) {
			return '';
		}

		return $this->prefix.\implode($this->separator, $parts);
	}

	/**
	 * Renders a key/value pair.
	 *
	 * If the key does not exist (i.e., is `null`), then the combination is
	 * equivalent to just the value. Otherwise, if the value is empty and
	 * form-style parameters are not required, the result is just the encoded
	 * key. Finally, if the key is defined, and either the value is empty or
	 * form-style parameters are required, the result is the encoded key,
	 * followed by "=", followed by the value.
	 *
	 * @param string|null $key
	 * The key to render, or `null` if no key should be rendered.
	 *
	 * @param string|null $value
	 * The value to associate with the key. It is assumed that this is the
	 * expansion of an expression, so it doesn't not require further percent
	 * encoding. If `null`, the result will be `null`.
	 *
	 * @return string|null
	 * The combination of the key and the value, or `null` if there is no value.
	 */
	public function combineKeyWithValue($key, $value) {
		// We assume value to be expanded (and, thus, appropriately encoded already).
		if (\is_null($value)) {
			return null;
		}
		else if (\is_null($key)) {
			return $value;
		}
		else if (\strlen($value) || $this->requireFormStyleParameters) {
			return $this->encode($key).'='.$value;
		}
		else {
			return $this->encode($key);
		}
	}

	/**
	 * Renders a string into the permitted set of characters.
	 *
	 * Any character outside the permitted set will be percent encoded.

	 * @param string $string
	 * The string to encode.
	 *
	 * @return string
	 * The encoded string.
	 */
	public function encode($string) {
		if ($this->permitSpecialCharacters) {
			$keepPercentage = true;
			$charTypes = [$this->charTypes->unreserved, $this->charTypes->reserved];
		}
		else {
			$keepPercentage = false;
			$charTypes = [$this->charTypes->unreserved];
		}

		return \Uri\percentEncode($string, $keepPercentage, ...$charTypes);
	}

	/**
	 * Expands a value in a non-exploded context.
	 *
	 * In a non-exploded context, lists and associtive arrays are treated as a
	 * whole and rendered into a string.
	 *
	 * @param mixed $value
	 * The value to expand in a non-exploded context.
	 *
	 * @return string
	 * The expansion of the value.
	 */
	public function simpleExpandValue($value) {
		return (new ValueDispatcher)->handle(
			$value,
			null,
			[
				'string' => function ($value) {
					return $this->encode($value);
				},

				'array' => function ($array, $isSequential) {
					$format = (
						$isSequential
						? function ($key, $value) {
							return $this->encode($value);
						}
						: function ($key, $value) {
							return $this->encode($key).','.$this->encode($value);
						}
					);

					$parts = [];
					foreach ($array as $key => $value) {
						if (\is_null($value)) {
							continue;
						}

						$parts[] = $format($key, $value);
					}

					if (empty($parts)) {
						return null;
					}

					return \implode(',', $parts);
				}
			]
		);
	}
}
?>