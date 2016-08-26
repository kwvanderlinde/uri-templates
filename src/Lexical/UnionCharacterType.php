<?php
namespace Uri\Lexical;

/**
 * Represents a union of character sets.
 *
 * @since 1.0.0
 */
class UnionCharacterType extends AbstractCharacterType implements CharacterType {
	/**
	 * @var CharacterType[]
	 * The set of types which are unioned together.
	 */
	private $types;

	/**
	 * Initializes a `UnionCharacterType`.
	 *
	 * `$this` will be equivalent to the union of all `$types`.
	 *
	 * @param CharacterType ...$types
	 * The sets which will be unioned together to form a new character set.
	 *
	 * @since 1.0.0
	 */
	public function __construct(CharacterType ...$types) {
		$this->types = $types;
	}

	/**
	 * @inheritDocs
	 */
	public function getRegexFragment() {
		return \implode(
			'|',
			\array_map(
				static function ($type) { return $type->getRegexFragment(); },
				$this->types
			)
		);
	}

	/**
	 * @inheritDocs
	 */
	public function or_(CharacterType $other) {
		$types = $this->types;
		\array_push($types, $other);

		return new self(...$types);
	}
}
?>