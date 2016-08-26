<?php
namespace Uri\Template\Parts;

use \Base\Exceptions\LogicError;
use \Uri\Lexical\CharacterTypes;
use \Uri\Template\Variables\Variable;
use \Uri\Template\Operator;
use \Uri\Template\ValueDispatcher;

/**
 * Represents an expression part of a URI template.
 *
 * An expression allows injecting values into a URI template. The exact rules
 * for this injection depends on the expression's operator and variable
 * specifications.
 */
class Expression implements Part {
	/**
	 * @var CharacterTypes
	 * The defined character sets.
	 */
	private $charTypes;

	/**
	 * @var Operator
	 * The operator of the expression.
	 */
	private $operator;

	/**
	 * @var array<Variable> $variables
	 * The variables which the expression will expand.
	 */
	private $variables;

	/**
	 * Initializes an `Expression` instance.
	 *
	 * @param CharacterTypes $charTypes
	 * The defined character sets.
	 *
	 * @param Operator $operator
	 * The operator defining the expression's semantics.
	 *
	 * @param array<Variable> $variables
	 * The variables which will be expanded by the expression.
	 */
	public function __construct(CharacterTypes $charTypes, Operator $operator, array $variables) {
		$this->charTypes = $charTypes;
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

		return $this->operator->combineValue($parts);
	}
}
?>