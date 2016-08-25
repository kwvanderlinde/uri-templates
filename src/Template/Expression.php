<?php
namespace Uri\Template;

use \Base\Exceptions\LogicError;
use \Uri\Lexical\CharacterTypes;

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

			$expandedParts = $this->expandValue($var, $value);

			$parts = \array_merge($parts, \iterator_to_array($expandedParts));
		}

		return $this->operator->combineValue($parts);
	}

	protected function expandValue(Variable $var, $value) {
		if ($this->operator->expandNamedParameters()) {
			$prefixVar = $var->getName();
		}
		else {
			$prefixVar = null;
		}

		if (\is_null($value)) {
			yield $value;
		}
		else if (\is_array($value)) {
			if (!$var->isExploded()) {
				// Do not explode the composite value.
				$expandedValue = $this->expandNotExplodedValue($value);
				yield $this->expandKeyValueImpl($prefixVar, $expandedValue);
			}
			else {
				// Explode the composite value.
				$getKey = \Uri\isSequentialArray($value)
					? static function ($key) use ($prefixVar) { return $prefixVar; }
					: static function ($key) { return $key; };

				foreach ($value as $key => $v) {
					yield $this->expandKeyValueImpl(
						$getKey($key),
						$this->expandNotExplodedValue($v)
					);
				}
			}
		}
		else {
			$value = (string)$value;
			// Exploded strings are the same as non-exploded strings.
			$expandedValue = $var->getValuePrefix($this->expandNotExplodedValue($value));
			yield $this->expandKeyValueImpl($prefixVar, $expandedValue);
		}
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
		if (\is_null($value)) {
			return null;
		}
		else if (\is_array($value)) {
			$parts = [];
			if (\Uri\isSequentialArray($value)) {
				foreach ($value as $v) {
					if (\is_null($v)) {
						continue;
					}

					$parts[] = $this->operator->encode($v);
				}
			}
			else {
				foreach ($value as $k => $v) {
					if (\is_null($v)) {
						continue;
					}

					$parts[] = $this->operator->encode($k).','.$this->operator->encode($v);
				}
			}

			if (empty($parts)) {
				return null;
			}

			return \implode(',', $parts);
		}
		else {
			$value = (string)$value;
			return $this->operator->encode($value);
		}
	}
}
?>