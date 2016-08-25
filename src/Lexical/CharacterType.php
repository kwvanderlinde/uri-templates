<?php
namespace Uri\Lexical;

interface CharacterType {
	/**
	 * Tests whether a character belongs to this character type.
	 *
	 * @param string $character
	 * The character to test. If the string has more than one character, the
	 * result should be `false`.
	 *
	 * @return bool
	 * `true` if `$character` belongs to this type. Otherwise `false`.
	 */
	function contains($character);

	/**
	 * Gets a regular expression fragment which can be used to match characters
	 * in this type.
	 *
	 * For example, if the character type represents digits, a valid result
	 * would be `[0-9]`.
	 *
	 * This method must be compatible with `contains`. That is, we should have
	 *
	 *     $this->contains($char) === \preg_match($this->getRegexFragment(), $char)
	 *
	 * @return string
	 * A regex fragment which matches any character in the type.
	 */
	function getRegexFragment();

	function or_(CharacterType $other);

	/**
	 * An alias of `getRegexFragment`.
	 */
	function __toString();
}
?>