<?php
namespace Uri\Template\Parts;

use \Uri\Lexical\CharacterTypes;

/**
 * Represents a portion of a URI template which expands to itself.
 *
 * The expansion of the literal may not have the identical representation as in
 * the source string because all characters outside the "literals" character set
 * are percent encoded.
 *
 * @since 1.0.0
 */
class Literal implements Part {
	/**
	 * @var CharacterTypes
	 * The defined character types.
	 */
	private $charTypes;

	/**
	 * @var string
	 * The percent encoded version of the input string.
	 */
	private $string;

	/**
	 * Initializes a `Literal` instance.
	 *
	 * @param CharacterTypes $charTypes
	 * The defined character types.
	 *
	 * @param string $string
	 * The string which should be expanded literally.
	 *
	 * @since 1.0.0
	 */
	public function __construct(CharacterTypes $charTypes, $string) {
		$this->charTypes = $charTypes;
		// We expand right away. There's no need to wait.
		$this->string = \Uri\percentEncode(
			$string,
			true,
			$this->charTypes->unreserved,
			$this->charTypes->reserved
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $variables
	 * The variables used in expansion. This parameter is not used for literals.
	 *
	 * @return string
	 * The input string after percent encoding.
	 *
	 * @since 1.0.0
	 */
	public function expand(array $variables) {
		return $this->string;
	}
}
?>