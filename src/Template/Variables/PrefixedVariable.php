<?php
namespace Uri\Template\Variables;

class PrefixedVariable extends AbstractVariable {
	private $prefixCount;

	public function __construct($name, $prefixCount) {
		parent::__construct($name);

		$this->prefixCount = $prefixCount;
	}

	public function getPrefixCount() {
		return $this->prefixCount;
	}
}
?>