<?php
namespace Uri\Lexical;

class UnionCharacterType extends AbstractCharacterType implements CharacterType {
	private $types;

	public function __construct(CharacterType ...$types) {
		$this->types = $types;
	}

	public function getRegexFragment() {
		return \implode(
			'|',
			\array_map(
				static function ($type) { return $type->getRegexFragment(); },
				$this->types
			)
		);
	}

	public function or_(CharacterType $other) {
		$types = $this->types;
		\array_push($types, $other);

		return new self(...$types);
	}
}
?>