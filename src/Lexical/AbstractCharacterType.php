<?php
namespace Uri\Lexical;

abstract class AbstractCharacterType implements CharacterType {
	public function or_(CharacterType $other) {
		return new UnionCharacterType($this, $other);
	}

	public function __toString() {
		return $this->getRegexFragment();
	}
}
?>