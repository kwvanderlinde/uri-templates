<?php
namespace Uri\Template\Parts;

use \Uri\Lexical\CharacterTypes;
use \Uri\Template\Operator;

/**
 * Represents an expression part of a URI template.
 *
 * An expression allows injecting values into a URI template. The exact rules
 * for this injection depends on the expression's operator and variable
 * specifications.
 */
class Expression implements Part {
	/**
	 * @var Operator
	 * The operator of the expression.
	 */
	private $operator;

	/**
	 * @var \Uri\Template\Variables\Variable[] $variables
	 * The variables which the expression will expand.
	 */
	private $variables;

	/**
	 * Initializes an `Expression` instance.
	 *
	 * @param Operator $operator
	 * The operator defining the expression's semantics.
	 *
	 * @param \Uri\Template\Variables\Variable[] $variables
	 * The variables which will be expanded by the expression.
	 */
	public function __construct(Operator $operator, array $variables) {
		$this->operator = $operator;
		$this->variables = $variables;
	}

	/**
	 * @inheritDoc
	 */
	public function expand(array $variables) {
		$parts = array();
		foreach ($this->variables as $var) {
			$parts = \array_merge(
				$parts,
				\iterator_to_array(
					$var->expand($variables, $this->operator)
				)
			);
		}

		return $this->operator->combineValues($parts);
	}
}
?>