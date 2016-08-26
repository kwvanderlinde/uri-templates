<?php
namespace Uri\Template\Variables;

use \Uri\Template\Operator;
use \Uri\Template\ValueDispatcher;

/**
 * A `Variable` composed out of independent strategies.
 *
 * @since 1.0.0
 */
class ComposedVariable implements Variable {
	/**
	 * @var string
	 * The name of the variable.
	 */
	private $name;

	/**
	 * @var callable
	 * Defines the behaviour for expanding lists.
	 */
	private $listExpander;

	/**
	 * @var callable
	 * Defines the behaviour for expanding associative arrays.
	 */
	private $assocExpander;

	/**
	 * @var callable
	 * Defines the behaviour for trimming results of expansion.
	 */
	private $valuePrefixer;

	/**
	 * initializes an `AbstractVariable` instance.
	 *
	 * @param string $name
	 * The name of the variable.
	 *
	 * @param callable $listExpander
	 * The behaviour for expanding lists.
	 *
	 * @param callable $assocExpander
	 * The behaviour for expanding associative arrays..
	 *
	 * @param callable $valuePrefixer
	 * The behaviour for trimming expansion results.
	 *
	 * @since 1.0.0
	 */
	public function __construct($name, callable $listExpander, callable $assocExpander, callable $valuePrefixer) {
		$this->name = $name;
		$this->listExpander = $listExpander;
		$this->assocExpander = $assocExpander;
		$this->valuePrefixer = $valuePrefixer;
	}

	/**
	 * @inheritDocs
	 */
	public function expand(array $variables, Operator $operator) {
		$prefixVar = $operator->chooseDefaultKey($this->name);

		return (new ValueDispatcher(@$variables[$this->name], new \EmptyIterator))->handle(
			[
				'string' => function ($value) use ($prefixVar, $operator) {
					// Exploded strings are the same as non-exploded strings.
					yield $operator->combineKeyWithValue(
						$prefixVar,
						\call_user_func(
							$this->valuePrefixer,
							$operator->simpleExpandValue($value)
						)
					);
				},

				'list' => function (array $value) use ($prefixVar, $operator) {
					return $this->expandArray(
						$value,
						$prefixVar,
						$operator,
						$this->listExpander
					);
				},

				'assoc' => function (array $value) use ($prefixVar, $operator) {
					return $this->expandArray(
						$value,
						$prefixVar,
						$operator,
						$this->assocExpander
					);
				}
			]
		);
	}

	/**
	 * Handles expansion of list and associative array values.
	 *
	 * @param mixed[] $value
	 * The values to be expanded.
	 *
	 * @param string|null $prefixVar
	 * The variable name to use as a key for unassociated values.
	 *
	 * @param Operator $operator
	 * The operator of the expression, which defines the semantics for value
	 * expansion.
	 *
	 * @param callable $exploder
	 * Explodes the array if applicable.
	 *
	 * @return Generator<string|null>
	 * A sequence of strings, one for each defined value.
	 *
	 * @since 1.0.0
	 */
	private function expandArray(array $value, $prefixVar, Operator $operator, callable $exploder) {
		$keyValuePairs = \call_user_func($exploder, $value,	$prefixVar);

		foreach ($keyValuePairs as list($key, $value)) {
			yield $operator->combineKeyWithValue(
				$key,
				$operator->simpleExpandValue($value)
			);
		}
	}
}
?>