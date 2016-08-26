<?php
namespace Uri\Template\Variables;

use \Uri\Template\Operator;

/**
 * Handles creation of `Variable` instances.
 */
class Factory {
	/**
	 * Creates a simple string-substituted variable.
	 *
	 * No explosion or prefix extraction will occur with the resulting variable.
	 *
	 * @param string $name
	 * The name of the variable.
	 *
	 * @return Variable
	 * A variable with the given name and no modifiers.
	 */
	public function createSimple($name) {
		return new ComposedVariable(
			$name,
			static function ($prefixVar, $value, Operator $operator) {
				yield [ $prefixVar, $value ];
			},
			static function ($value) {
				return $value;
			}
		);
	}

	/**
	 * Creates a variable with an explosion modifier.
	 *
	 * @param string $name
	 * The name of the variable.
	 *
	 * @return Variable
	 * A variable with the given name which will explode arrays during
	 * expansion.
	 */
	public function createExploded($name) {
		return new ComposedVariable(
			$name,
			static function ($prefixVar, $value, Operator $operator) {
				// Explode the composite value.
				$getKey = \Uri\isSequentialArray($value)
					? static function ($key) use ($prefixVar) { return $prefixVar; }
					: static function ($key) { return $key; };

				foreach ($value as $key => $v) {
					yield [ $getKey($key), $v ];
				}
			},
			static function ($value) {
				return $value;
			}
		);
	}

	/**
	 * Creates a variable with a prefixing modifier
	 *
	 * @param string $name
	 * The name of the variable.
	 *
	 * @param int $prefixCount
	 * The number of characters to extract from the front of an expansion.
	 *
	 * @return Variable
	 * A variable with the given name which will extract a prefix from the
	 * expansion.
	 */
	public function createPrefixed($name, $prefixCount) {
		return new ComposedVariable(
			$name,
			static function ($prefixVar, $value, Operator $operator) {
				yield [ $prefixVar, $value ];
			},
			static function ($value) use ($prefixCount) {
				$result = '';

				$remaining = $value;
				for ($takenCount = 0; $takenCount < $prefixCount && \strlen($remaining); ++$takenCount) {
					$regexResult = \preg_match(
						'/(?<char>(?:%[0-9A-Fa-f]{2}|[^%]))(?<remaining>\X*)/u',
						$remaining,
						$matches
					);
					if (!$regexResult) {
						break;
					}

					$result .= $matches['char'];
					$remaining = $matches['remaining'];

				}

				return $result;
			}
		);
	}
}
?>