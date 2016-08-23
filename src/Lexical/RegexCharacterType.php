<?php
namespace Uri\Lexical;

/**
 * @brief
 * A category of characters.
 */
class RegexCharacterType extends AbstractCharacterType implements CharacterType {
	private $regex;

	/**
	 * @brief
	 * Initializes a `CharType` with a given matcher.
	 *
	 * @param string $regex
	 * A regex fragment which can be used to match a single character in the
	 * character set.
	 */
	public function __construct($regex) {
		$this->regex = $regex;
	}

	public function contains($character) {
		return \preg_match("/{$this->getRegexFragment()}/u", $character);
	}

	public function getRegexFragment() {
		return $this->regex;
	}
}
?>