<?php
namespace Uri\Template;

abstract class AbstractVariable implements Variable {
	private $name;

	public function __construct($name) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}

	public function getPrefixCount() {
		return 0;
	}

	public function isExploded() {
		return false;
	}

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