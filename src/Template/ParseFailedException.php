<?php
namespace Uri\Template;

/**
 * Represents a failed parse.
 *
 * @since 1.0.0
 */
class ParseFailedException extends \Base\Exceptions\Contingency {
	/**
	 * @var string
	 * The input string on which the parser failed.
	 */
	private $string;

	/**
	 * Initializes a `ParseFailedException`
	 *
	 * @param string $message
	 * A descriptive message explaining the conditions under which the exception
	 * was thrown.
	 *
	 * @param string $string
	 * The input string on which the parser failed.
	 *
	 * @since 1.0.0
	 */
	public function __construct($message, $string) {
		parent::__construct($message);

		$this->string = $string;
	}

	/**
	 * Gets the input string.
	 *
	 * @return string
	 * The input string.
	 *
	 * @since 1.0.0
	 */
	public function getString() {
		return $this->string;
	}
}
?>