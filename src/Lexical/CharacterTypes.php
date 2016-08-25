<?php
namespace Uri\Lexical;

use \Base\Exceptions\LogicError;

class CharacterTypes {
	private $types;

	public function __construct() {
		$this->types = array();
	}

	public function __isset($name) {
		return isset($this->types[$name]);
	}

	public function __get($name) {
		if (!$this->__isset($name)) {
			throw new LogicError("Unrecognized character type '$name'");
		}

		return $this->types[$name];
	}

	public function __set($name, CharacterType $type) {
		$this->types[$name] = $type;
	}

	public function __unset($name) {
		unset($type->types[$name]);
	}
}
?>