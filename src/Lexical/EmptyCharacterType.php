<?php
namespace Uri\Lexical;

class EmptyCharacterType extends AbstractCharacterType implements CharacterType {
	public function contains($character) {
		return false;
	}

	public function getRegexFragment() {
		// Convention: "[\s\S]" means "anything", so "[^\s\S]" means "nothing".
		return '[^\s\S]';
	}
}
?>