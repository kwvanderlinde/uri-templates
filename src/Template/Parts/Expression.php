<?php
namespace Uri\Template\Parts;

use \Base\Exceptions\LogicError;
use \Uri\Lexical\CharacterTypes;
use \Uri\Template\Variables\Variable;
use \Uri\Template\Operator;
use \Uri\Template\ValueDispatcher;

class Expression implements Part {
	private $charTypes;
	private $operator;
	private $variables;

	public function __construct(CharacterTypes $charTypes, Operator $operator, array $variables) {
		$this->charTypes = $charTypes;
		$this->operator = $operator;
		$this->variables = $variables;
	}

	public function expand(array $variables) {
		$parts = array();
		foreach ($this->variables as $var) {
			$value = @$variables[$var->getName()];

			$expandedParts = \iterator_to_array($this->expandValue($var, $value));

			$parts = \array_merge($parts, $expandedParts);
		}

		return $this->operator->combineValue($parts);
	}

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

	protected function expandKeyValueImpl($key = null, $value) {
		/* Any explosions have already taken place, so we don't have to exploded
		 *`$value` here.
		 */
		if (\is_null($value)) {
			return null;
		}

		return $this->operator->combineKeyWithValue($key, $value);
	}

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