<?php
namespace Uri\Template;

use \Uri\Lexical\CharacterTypes;

class Operator {
	private $charTypes;
	private $prefix;
	private $separator;
	private $expandNamedParameters;
	private $requireFormStyleParameters;
	private $permitSpecialCharacters;

	public function __construct(CharacterTypes $charTypes, $prefix, $separator, $expandNamedParameters, $requireFormStyleParameters, $permitSpecialCharacters) {
		$this->charTypes = $charTypes;
		$this->prefix = (string)$prefix;
		$this->separator = (string)$separator;
		$this->expandNamedParameters = (bool)$expandNamedParameters;
		$this->requireFormStyleParameters = (bool)$requireFormStyleParameters;
		$this->permitSpecialCharacters = $permitSpecialCharacters;
	}

	public function expandNamedParameters() {
		return $this->expandNamedParameters;
	}

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

	public function combineKeyWithValue($key, $value) {
		// We assume value to be expanded (and, thus, appropriately encoded already).
		if (\is_null($key)) {
			return $value;
		}
		else if (\strlen($value) || $this->requireFormStyleParameters) {
			return $this->encode($key).'='.$value;
		}
		else {
			return $this->encode($key);
		}
	}

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
}
?>