<?php
namespace Uri\Lexical;

use \Base\Exceptions\LogicError;

/**
 * Represents a collection of named character sets.
 *
 * @since 1.0.0
 */
class CharacterTypes {
	/**
	 * @var CharacterType[]
	 * A mapping from names to character sets.
	 */
	private $types;

	/**
	 * Initializes a `CharacterTypes` instance.
	 *
	 * The new instance will not have any defined character sets.
	 */
	public function __construct() {
		$this->types = array();
	}

	/**
	 * Checks whether a character set exists by the given name.
	 *
	 * @param string $name
	 * The name to check.
	 *
	 * @return bool
	 * Whether a character set has been defined for `$name`.
	 *
	 * @since 1.0.0
	 */
	public function __isset($name) {
		return isset($this->types[$name]);
	}

	/**
	 * Retrieves a character set by name.
	 *
	 * @param string $name
	 * The name to lookup.
	 *
	 * @return CharacterType
	 * The character set associated with `$name`.
	 *
	 * @since 1.0.0
	 */
	public function __get($name) {
		if (!$this->__isset($name)) {
			throw new LogicError("Unrecognized character type '$name'");
		}

		return $this->types[$name];
	}

	/**
	 * Associates a character set with a name.
	 *
	 * If a character set is already associated with the given name, it is
	 * replaced in favour of the new character set.
	 *
	 * @param string $name
	 * The name to which to the character set will be associated.
	 *
	 * @param CharacterType $type
	 * The character set to associated with `$name`.
	 *
	 * @since 1.0.0
	 */
	public function __set($name, CharacterType $type) {
		$this->types[$name] = $type;
	}

	/**
	 * Removes any association to a name.
	 *
	 * @param string $name
	 * The name whose association will be removed.
	 *
	 * @since 1.0.0
	 */
	public function __unset($name) {
		unset($this->types[$name]);
	}
}
?>