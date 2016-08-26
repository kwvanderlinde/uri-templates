<?php
namespace Uri\Lexical;

/**
 * A category of characters.
 */
class RegexCharacterType extends AbstractCharacterType implements CharacterType {
	private $regex;

	/**
	 * Initializes a `CharType` with a given matcher.
	 *
	 * @param string $regex
	 * A regex fragment which can be used to match a single character in the
	 * character set.
	 */
	public function __construct($regex) {
		$this->regex = $regex;
	}

	/**
	 * @inheritDocs
	 */
	public function getRegexFragment() {
		return $this->regex;
	}
}
?>