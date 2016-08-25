<?php
namespace Uri;

use \Uri\Template\Parts\Part as TemplatePart;

class Template {
	private $parts;

	public function __construct(TemplatePart ...$parts) {
		$this->parts = $parts;
	}

	public function expand(array $variables) {
		$expandedParts = \array_map(
			static function (TemplatePart $part) use ($variables) {
				return $part->expand($variables);
			},
			$this->parts
		);

		return \implode('', $expandedParts);
	}
}
?>