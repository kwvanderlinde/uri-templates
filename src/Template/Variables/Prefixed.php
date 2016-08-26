<?php
namespace Uri\Template\Variables;

/**
 * Represents a variable which extracts a prefix from the results of its
 * expansions.
 */
class Prefixed extends AbstractVariable {
	/**
	 * @var int
	 * The number of characters to keep from the front of a value's expansion.
	 */
	private $prefixCount;

	/**
	 * Initializes a `Prefixed` instance.
	 *
	 * @param string $name
	 * The name of the variable.
	 *
	 * @param int $prefixCount
	 * The number of characters to keep from the front of a value's expansion.
	 */
	public function __construct($name, $prefixCount) {
		parent::__construct($name);

		$this->prefixCount = $prefixCount;
	}

	/**
	 * @inheritDocs
	 */
	public function getPrefixCount() {
		return $this->prefixCount;
	}
}
?>