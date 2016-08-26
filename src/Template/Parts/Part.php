<?php
namespace Uri\Template\Parts;

/**
 * Represents a piece of a URI template.
 *
 * @since 1.0.0
 */
interface Part {
	/**
	 * Expands the part with a given set of variables.
	 *
	 * @param array $variables
	 * The variables to used in the expansions. The keys are the variable
	 * names, and the values are the corresponding variable values.
	 *
	 * @return string
	 * The result of URI template expansion applied to the given variables.
	 *
	 * @since 1.0.0
	 */
	function expand(array $variables);
}
?>