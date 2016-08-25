<?php
namespace Uri\Template;

use \Base\Exceptions\LogicError;

use \Uri\Lexical\CharacterTypes;
use \Uri\Lexical\RegexCharacterType;

class Parser {
	private $characterTypes;
	private $operators;

	public function __construct() {
		$this->characterTypes = $this->buildCharacterTypes();

		$this->operators = array(
			'' => $this->makeOperator('', ',', false, false, false),
			'+' => $this->makeOperator('', ',', false, false, true),
			'#' => $this->makeOperator('#', ',', false, false, true),
			'.' => $this->makeOperator('.', '.', false, false, false),
			'/' => $this->makeOperator('/', '/', false, false, false),
			';' => $this->makeOperator(';', ';', true, false, false),
			'?' => $this->makeOperator('?', '&', true, true, false),
			'&' => $this->makeOperator('&', '&', true, true, false),
		);
	}

	private function makeOperator($prefix, $separator, $expandNamedParameters, $requireFormStyleParameters, $permitSpecialCharacters) {
		return new Operator($this->characterTypes, $prefix, $separator, $expandNamedParameters, $requireFormStyleParameters, $permitSpecialCharacters);
	}

	private function buildCharacterTypes() {
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

	public function parse($templateString) {
		$parts = array();

		$remaining = $templateString;

		$rest = '';
		while (\strlen($remaining)) {
			// Either match a literal or an expression,
			if ($remaining[0] === '{') {
				// Expression.
				list($expression, $rest) = $this->parseExpression($remaining);
				if ($expression === false) {
					throw new LogicError('Expected expression or literal at '.(\strlen($templateString) - \strlen($rest))." in '$templateString'");
				}
				$parts[] = $expression;
			}
			else {
				// Literal
				list($literal, $rest) = $this->parseLiteral($remaining);
				if ($literal === false) {
					throw new LogicError('Expected expression or literal at '.(\strlen($templateString) - \strlen($rest))." in '$templateString'");
				}

				$parts[] = $literal;
			}

			$remaining = $rest;
		}

		if (\strlen($rest)) {
			throw new LogicError("Extra characters after URI template: '$rest'");
		}

		return new \Uri\Template(...$parts);
	}

	protected function parseExpression($string) {
		if (!\preg_match("/^\\{(?<operator>{$this->characterTypes->operator})?(?<variables>{$this->getVarSpecRegex()}(?:,{$this->getVarSpecRegex()})*)\\}(?<rest>\X*)/u", $string, $matches)) {
			return [false, $string];
		}

		$operatorName = $matches['operator'];
		if (!\array_key_exists($operatorName, $this->operators)) {
			return [false, $string];
		}
		$operator = $this->operators[$operatorName];
		$variables = \explode(',', $matches['variables']);

		// Parse out each of the variables.
		$variables = \array_map(
			static function ($variable) {
				if (\preg_match('/(?<varname>\X*)\\*$/u', $variable, $matches)) {
					return new ExplodedVariable($matches['varname']);
				}
				else if (\preg_match('/(?<varname>\X*):(?<prefixCount>[0-9]*)/u', $variable, $matches)) {
					return new PrefixedVariable($matches['varname'], (int)$matches['prefixCount']);
				}
				else {
					// Regular variable
					return new SimpleVariable($variable);
				}
			},
			$variables
		);

		$expression = new Expression($this->characterTypes, $operator, $variables);

		return [$expression, $matches['rest']];
	}

	protected function parseLiteral($string) {
		if (!\preg_match("/^(?<literal>(?:{$this->getLiteralCharRegex()})*)(?<rest>\X*)$/u", $string, $matches)) {
			return [false, $string];
		}
		$literal = $matches['literal'];
		if (\strlen($literal)) {
			$literal = new Literal($this->characterTypes, $literal);
		}
		else {
			$literal = false;
		}

		return [$literal, $matches['rest']];
	}

	protected function getVarSpecRegex() {
		// TODO Permit level-4 modifier.
		return "{$this->getVarNameRegex()}(?:{$this->getLevel4ModifierRegex()})?";
	}

	protected function getLevel4ModifierRegex() {
		return "{$this->getPrefixRegex()}|{$this->getExplodeRegex()}";
	}

	protected function getPrefixRegex() {
		// Colon followed by positive integer < 10000.
		return ":[1-9][0-9]{0,3}";
	}

	protected function getExplodeRegex() {
		return "\\*";
	}

	protected function getVarNameRegex() {
		$varChar = $this->getVarCharRegex();
		return "$varChar(?:\\.?$varChar)*";
	}

	protected function getVarCharRegex() {
		return "(?:[A-Za-z0-9_]|{$this->getPercentEncodedRegex()})";
	}

	protected function getLiteralCharRegex() {
		return '[!#$&()*+,\\-.\\/0-9:;=?@A-Z[\\]_a-z~]'
			."|(?:{$this->characterTypes->ucschar})|(?:{$this->characterTypes->iprivate})"
			."|(?:{$this->getPercentEncodedRegex()})";
	}

	protected function getPercentEncodedRegex() {
		return "%{$this->characterTypes->hexDigit}{2}";
	}
}
?>