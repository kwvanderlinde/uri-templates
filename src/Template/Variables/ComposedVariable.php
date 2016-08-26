<?php
namespace Uri\Template\Variables;

use \Uri\Template\ValueDispatcher;

class ComposedVariable implements Variable {
	/**
	 * @var string
	 * The name of the variable.
	 */
	private $name;

	/**
	 * @var callable
	 * Defines the behaviour for expanding arrays.
	 */
	private $arrayExpander;

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
	 * @param callable $arrayExpander
	 * The behaviour for expanding arrays.
	 *
	 * @param callable $valuePrefix
	 * The behaviour for trimming expansion results.
	 */
	public function __construct($name, callable $arrayExpander, callable $valuePrefixer) {
		$this->name = $name;
		$this->arrayExpander = $arrayExpander;
		$this->valuePrefixer = $valuePrefixer;
	}

	/**
	 * @inheritDocs
	 */
	public function expand(array $variables, \Uri\Template\Operator $operator) {
		$prefixVar = $operator->getDefaultKey($this->name);

		return (new ValueDispatcher)->handle(
			@$variables[$this->name],
			new \EmptyIterator,
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

				'array' => function ($value) use ($prefixVar, $operator) {
					$keyValuePairs = \call_user_func(
						$this->arrayExpander,
						$prefixVar,
						$value,
						$operator
					);

					foreach ($keyValuePairs as list($key, $value)) {
						yield $operator->combineKeyWithValue(
							$key,
							$operator->simpleExpandValue($value)
						);
					}
				}
			]
		);
	}
}
?>