<?php
namespace Uri\Template\Parts;

use \Base\Exceptions\LogicError;
use \Uri\Lexical\CharacterTypes;
use \Uri\Template\Variables\Variable;
use \Uri\Template\Operator;
use \Uri\Template\ValueDispatcher;

/**
 * Represents an expression part of a URI template.
 *
 * An expression allows injecting values into a URI template. The exact rules
 * for this injection depends on the expression's operator and variable
 * specifications.
 */
class Expression implements Part {
	/**
	 * @var CharacterTypes
	 * The defined character sets.
	 */
	private $charTypes;

	/**
	 * @var Operator
	 * The operator of the expression.
	 */
	private $operator;

	/**
	 * @var array<Variable> $variables
	 * The variables which the expression will expand.
	 */
	private $variables;

	/**
	 * Initializes an `Expression` instance.
	 *
	 * @param CharacterTypes $charTypes
	 * The defined character sets.
	 *
	 * @param Operator $operator
	 * The operator defining the expression's semantics.
	 *
	 * @param array<Variable> $variables
	 * The variables which will be expanded by the expression.
	 */
	public function __construct(CharacterTypes $charTypes, Operator $operator, array $variables) {
		$this->charTypes = $charTypes;
		$this->operator = $operator;
		$this->variables = $variables;
	}

	/**
	 * @inheritDoc
	 */
	public function expand(array $variables) {
		$parts = array();
		foreach ($this->variables as $var) {
			$value = @$variables[$var->getName()];

			$expandedParts = \iterator_to_array($this->expandValue($var, $value));

			$parts = \array_merge($parts, $expandedParts);
		}

		return $this->operator->combineValue($parts);
	}

	/**
	 * Expands a variable with a value.
	 *
	 * @param Variable $var
	 * The variable to expand.
	 *
	 * @param mixed $value
	 * The value to expand.
	 *
	 * @return string
	 * The expanded variable.
	 */
	protected function expandValue(Variable $var, $value) {
		$prefixVar = $this->operator->getDefaultKey($var);

		return (new ValueDispatcher)->handle(
			$value,
			new \EmptyIterator,
			[
				'string' => function ($value) use ($var, $prefixVar) {
					// Exploded strings are the same as non-exploded strings.
					$expandedValue = $var->getValuePrefix($this->expandNotExplodedValue($value));
					yield $this->expandKeyValueImpl($prefixVar, $expandedValue);
				},

				'array' => function ($value, $isSequential) use ($var, $prefixVar) {
					if (!$var->isExploded()) {
						// Do not explode the composite value.
						$expandedValue = $this->expandNotExplodedValue($value);
						$result = $this->expandKeyValueImpl($prefixVar, $expandedValue);
						yield $result;
					}
					else {
						// Explode the composite value.
						$getKey = $isSequential
						? static function ($key) use ($prefixVar) { return $prefixVar; }
						: static function ($key) { return $key; };

						foreach ($value as $key => $v) {
							$result = $this->expandKeyValueImpl(
								$getKey($key),
								$this->expandNotExplodedValue($v)
							);
							yield $result;
						}
					}
				}
			]
		);
	}

	/**
	 * Expands a key/value pair.
	 *
	 * @param string|null $key
	 * The key to used when expanding the pair. If `null`, no key will be used.
	 *
	 * @param string $value
	 * The expanded value to combine with the key.
	 *
	 * @return string
	 * The comination of the key and value, as defined by the current operator.
	 */
	protected function expandKeyValueImpl($key = null, $value) {
		/* Any explosions have already taken place, so we don't have to exploded
		 *`$value` here.
		 */
		if (\is_null($value)) {
			return null;
		}

		return $this->operator->combineKeyWithValue($key, $value);
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
	protected function expandNotExplodedValue($value) {
		return (new ValueDispatcher)->handle(
			$value,
			null,
			[
				'string' => function ($value) {
					return $this->operator->encode($value);
				},

				'array' => function ($array, $isSequential) {
					$format = (
						$isSequential
						? function ($key, $value) {
							return $this->operator->encode($value);
						}
						: function ($key, $value) {
							return $this->operator->encode($key).','.$this->operator->encode($value);
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