<?php
namespace Uri\Template\Variables;

/**
 * Represents a variable specification in an expression.
 *
 * A variable specification has several attributes associated with it. These are:
 *
 *   - The variable name. This name is used to lookup a value in a set of
 *     provided variables.
 *
 *   - A value modifier. This can either be an explosion modifier, in which case
 *     any values will be exploded, or a prefix modifier, in which a prefix of
 *     the values will be extracted as the final expansion result. Prefixing
 *     has no effect on non-string values, and explosion has no effect on string
 *     values.
 */
interface Variable {
	/**
	 * Expands a variable with a value.
	 *
	 * @param array $variables
	 * A mapping from variable names to values.
	 *
	 * @param Operator $operator
	 * The operator which defines the expansion semantics.
	 *
	 * @return string
	 * The expanded variable.
	 */
	function expand(array $variables, \Uri\Template\Operator $operator);
}
?>