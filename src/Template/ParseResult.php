<?php
namespace Uri\Template;

class ParseResult {
	private $payload;
	private $remainingInput;

	public function __construct($payload, $remainingInput) {
		$this->payload = $payload;
		$this->remainingInput = $remainingInput;
	}

	public function getPayload() {
		return $this->payload;
	}

	public function getRemainingInput() {
		return $this->remainingInput;
	}
}
?>