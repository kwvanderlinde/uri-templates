<?php
namespace Uri\Template\Variables;

/**
 * A helper for implementing `Variable`.
 */
abstract class AbstractVariable implements Variable {
	/**
	 * @var string $name
	 * The name of the variable.
	 */
	private $name;

	/**
	 * initializes an `AbstractVariable` instance.
	 *
	 * @param string $name
	 * The name of the variable.
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 */
	public function getPrefixCount() {
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function isExploded() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getValuePrefix($value) {
		if ($prefixCount = $this->getPrefixCount()) {
			$result = '';
			for ($i = 0, $charsTaken = 0, $n = \strlen($value); $i < $n && $charsTaken < $prefixCount; ++$charsTaken) {
				$char = $value[$i];

				if ($char === '%') {
					// This is guaranteed to be a properly percent encoded value on expanded strings.
					$result .= \substr($value, $i, 3);
					$i += 3;
				}
				else {
					$result .= $char;
					$i += 1;
				}
			}
			return $result;
		}
		else {
			return $value;
		}
	}
}
?>