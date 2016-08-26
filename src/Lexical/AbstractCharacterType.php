<?php
namespace Uri\Lexical;

/**
 * A helper class for defining character types.
 */
abstract class AbstractCharacterType implements CharacterType {
	/**
	 * @inheritDocs
	 */
	public function contains($char) {
		return \preg_match("/{$this->getRegexFragment()}/u", $char);
	}

	/**
	 * @inheritDocs
	 */
	public function or_(CharacterType $other) {
		return new UnionCharacterType($this, $other);
	}

	/**
	 * @inheritDocs
	 */
	public function __toString() {
		return $this->getRegexFragment();
	}
}
?>