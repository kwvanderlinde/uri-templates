<?php
namespace Uri\Template;

/**
 * Represents the result of a parse.
 *
 * A parse result consists of a parsed value and any unconsumed input.
 *
 * @since 1.0.0
 */
class ParseResult {
	/**
	 * @var mixed
	 * The parsed value.
	 */
	private $payload;

	/**
	 * @var string
	 * The input which is unconsumed after parsing the payload.
	 */
	private $remainingInput;

	/**
	 * Initializes a `ParseResult`.
	 *
	 * @param mixed $payload
	 * The parsed value.
	 *
	 * @param string $remainingInput
	 * The unconsumed input.
	 *
	 * @since 1.0.0
	 */
	public function __construct($payload, $remainingInput) {
		$this->payload = $payload;
		$this->remainingInput = $remainingInput;
	}

	/**
	 * Gets the parsed value.
	 *
	 * @return mixed
	 * The parsed value.
	 *
	 * @since 1.0.0
	 */
	public function getPayload() {
		return $this->payload;
	}

	/**
	 * Gets the unconsomed input.
	 *
	 * @return string
	 * The unconsumed input.
	 *
	 * @since 1.0.0
	 */
	public function getRemainingInput() {
		return $this->remainingInput;
	}
}
?>