<?php
namespace Uri\Template\Parts;

use \Uri\Lexical\CharacterTypes;

class Literal implements Part {
	private $charTypes;
	private $string;

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

	public function expand(array $variables) {
		return $this->string;
	}
}
?>