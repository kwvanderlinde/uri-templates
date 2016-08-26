<?php
namespace Uri\Template;

use \Uri\Lexical\CharacterTypes;
use \Uri\Lexical\RegexCharacterType;

class ParserFactory {
	public function create() {
		$characterTypes = $this->getCharacterTypes();
		$operators = $this->getOperators($characterTypes);
		return new Parser($operators, $characterTypes);
	}

	protected function getOperators(CharacterTypes $characterTypes) {
		return array(
			'' => new Operator($characterTypes, '', ',', false, false, false),
			'+' => new Operator($characterTypes, '', ',', false, false, true),
			'#' => new Operator($characterTypes, '#', ',', false, false, true),
			'.' => new Operator($characterTypes, '.', '.', false, false, false),
			'/' => new Operator($characterTypes, '/', '/', false, false, false),
			';' => new Operator($characterTypes, ';', ';', true, false, false),
			'?' => new Operator($characterTypes, '?', '&', true, true, false),
			'&' => new Operator($characterTypes, '&', '&', true, true, false),
		);
	}

	protected function getCharacterTypes() {
		$characterTypes = new CharacterTypes;

		$characterTypes->alpha = new RegexCharacterType('[A-Za-z]');
		$characterTypes->digit = new RegexCharacterType('[0-9]');
		$characterTypes->hexDigit =
			$characterTypes->digit->or_(
				new RegexCharacterType('[ABCDEFabcdef]')
			);

		$characterTypes->genDelims = new RegexCharacterType('[:\\/?#\\[\\]@]');
		$characterTypes->subDelims = new RegexCharacterType('[!$&\'()*+,;=]');
		$characterTypes->unreserved = new RegexCharacterType('[A-Za-z0-9\\-._~]');
		$characterTypes->reserved =
			$characterTypes->genDelims->or_(
				$characterTypes->subDelims
			);

		$characterTypes->opLevel2 = new RegexCharacterType('[+#]');
		$characterTypes->opLevel3 = new RegexCharacterType('[.\\/;?&]');
		$characterTypes->opReserve = new RegexCharacterType('[=,!@|]');
		$characterTypes->operator =
			$characterTypes->opLevel2->or_(
				$characterTypes->opLevel3
			)->or_(
				$characterTypes->opReserve
			);

		$characterTypes->ucschar = new RegexCharacterType(
			'[\\x{A0}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFEF}'
			.'\\x{10000}-\\x{1FFFD}\\x{20000}-\\x{2FFFD}\\x{30000}-\\x{3FFFD}'
			.'\\x{40000}-\\x{4FFFD}\\x{50000}-\\x{5FFFD}\\x{60000}-\\x{6FFFD}'
			.'\\x{70000}-\\x{7FFFD}\\x{80000}-\\x{8FFFD}\\x{90000}-\\x{9FFFD}'
			.'\\x{A0000}-\\x{AFFFD}\\x{B0000}-\\x{BFFFD}\\x{C0000}-\\x{CFFFD}'
			.'\\x{D0000}-\\x{DFFFD}\\x{E1000}-\\x{EFFFD}]'
		);

		$characterTypes->iprivate = new RegexCharacterType(
			'[\\x{E000}-\\x{F8FF}\\x{F0000}-\\x{FFFFD}\\x{100000}-\\x{10FFFD}]'
		);

		return $characterTypes;
	}
}
?>