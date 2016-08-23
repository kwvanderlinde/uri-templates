<?php
namespace Uri;

class Template {
	private $parts;

	public function __construct(Template\Part ...$parts) {
		$this->parts = $parts;
	}

	public function expand(array $variables) {
		$expandedParts = \array_map(
			static function (Template\Part $part) use ($variables) {
				return $part->expand($variables);
			},
			$this->parts
		);

		return \implode('', $expandedParts);
	}
}
?>