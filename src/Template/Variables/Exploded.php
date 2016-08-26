<?php
namespace Uri\Template\Variables;

/**
 * Represents a variable which exploded its values.
 */
class Exploded extends AbstractVariable {
	/**
	 * @inheritDocs
	 */
	public function isExploded() {
		return true;
	}
}
?>