<?php
namespace Uri;

use \Uri\Template\Parts\Part as TemplatePart;

/**
 * The parsed representation of a URI template.
 *
 * @api
 *
 * @since 1.0.0
 */
class Template {
	/**
	 * The parts of a template.
	 *
	 * Each part is essentially a minimal template in itself. Template expansion
	 * essentially consists of expanding the individual parts, and then joining
	 * the results together into a single string.
	 *
	 * @var TemplatePart[]
	 */
	private $parts;

	/**
	 * Constructs a `Template`.
	 *
	 * @param TemplatePart ...$parts
	 * A collection of objects representing the components of a URI template.
	 *
	 * @api
	 *
	 * @since 1.0.0
	 */
	public function __construct(TemplatePart ...$parts) {
		$this->parts = $parts;
	}

	/**
	 * Expands a URI template by variable substitution.
	 *
	 * It should be noted that URI template expansion occurs only accounting for
	 * the generic URI syntax. Thus, the resulting URI may not actually meet
	 * the syntactic requirements of a particular scheme.
	 *
	 * @param array $variables
	 * A set of variables to use in the expansion. The keys are variable names,
	 * which map to their respective values.
	 *
	 * @return string
	 * The expanded URI.
	 *
	 * @api
	 *
	 * @since 1.0.0
	 */
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