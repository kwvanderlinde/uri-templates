<?php
namespace Uri\Template;

class ParseFailedException extends \Base\Exceptions\Contingency {
	private $string;

	public function __construct($message, $string) {
		parent::__construct($message);

		$this->string = $string;
	}

	public function getString() {
		return $this->string;
	}
}
?>