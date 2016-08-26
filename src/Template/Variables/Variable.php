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
	 * Gets the name of the variable.
	 *
	 * @return string
	 * The name of the variable.
	 */
	function getName();

	/**
	 * Get the number of characters to take from the front of a value.
	 *
	 * @return int
	 * The number of characters to take from the front of an expanded value
	 * string, or `0` if no prefix extraction should occur.
	 */
	function getPrefixCount();

	/**
	 * Gets whether to explode values.
	 *
	 * @return bool
	 * Whether to explode values.
	 */
	function isExploded();

	/**
	 * Extracts a prefix from a value.
	 *
	 * @param string $value
	 * The expanded value of the variable.
	 *
	 * @return string
	 * A prefix of `$value` containing the necessary number of characters,
	 * accounting for percent encoded characters.
	 */
	function getValuePrefix($value);
}
?>