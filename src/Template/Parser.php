<?php
namespace Uri\Template;

use \Base\Exceptions\LogicError;

use \Uri\Lexical\CharacterTypes;
use \Uri\Lexical\RegexCharacterType;

use \Uri\Template\Parts\Expression;
use \Uri\Template\Parts\Literal;
use \Uri\Template\Parts\Part;

use \Uri\Template\Variables\Factory as VariableFactory;
use \Uri\Template\Variables\Exploded as ExplodedVariable;
use \Uri\Template\Variables\Prefixed as PrefixedVariable;
use \Uri\Template\Variables\Simple as SimpleVariable;

/**
 * A parser for URI templates.
 */
class Parser {
	/**
	 * @var CharacterTypes
	 * The defined character sets.
	 */
	private $characterTypes;

	/**
	 * @var Operator[]
	 * The defined set of the operators. The keys are the names of the
	 * respective operators.
	 */
	private $operators;

	/**
	 * Initializes the parser.
	 *
	 * The character sets and operators are defined here.
	 *
	 * @todo
	 * Inject the character sets and operators as parameters.
	 */
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

	/**
	 * Parses a string as a URI template.
	 *
	 * @param string $templateString
	 * The string to interpret as a URI template.
	 *
	 * @return \Uri\Template
	 * An representation of the URI template which is capable of performing
	 * variable expansion.
	 */
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

		return new \Uri\Template(...$parts);
	}

	/**
	 * Parses an expression from the front of a string.
	 *
	 * @param string $string
	 * The string from which to parse an expression.
	 *
	 * @return array
	 * An array of two values. The first value is the parsed
	 * `\Uri\Template\Parts\Part` instance which represents the expression, or
	 * `false` if the parsing failed. The second value is the string remaining
	 * after parsing, or just `$string` in case of failure.
	 *
	 * @todo
	 * Define a contingency to throw on parse failures.
	 *
	 * @todo
	 * Define a type for parse results which encapsulate a parsed value with a
	 * remainder string.
	 */
	protected function parseExpression($string) {
		if (!\preg_match("/^\\{(?<operator>{$this->characterTypes->operator})?(?<variables>{$this->getVarSpecRegex()}(?:,{$this->getVarSpecRegex()})*)\\}(?<rest>\X*)/u", $string, $matches)) {
			return [false, $string];
		}

		$operatorName = $matches['operator'];
		if (!\array_key_exists($operatorName, $this->operators)) {
			// @codeCoverageIgnoreStart
			throw new LogicError("Missing operator object for '$operatorName'");
			// @codeCoverageIgnoreEnd
		}
		$operator = $this->operators[$operatorName];
		$variables = \explode(',', $matches['variables']);

		// Parse out each of the variables.
		$variableFactory = new VariableFactory;
		$variables = \array_map(
			static function ($variable) use ($variableFactory) {
				if (\preg_match('/(?<varname>\X*)\\*$/u', $variable, $matches)) {
					return $variableFactory->createExploded($matches['varname']);
				}
				else if (\preg_match('/(?<varname>\X*):(?<prefixCount>[0-9]*)/u', $variable, $matches)) {
					return $variableFactory->createPrefixed($matches['varname'], (int)$matches['prefixCount']);
				}
				else {
					// Regular variable
					return $variableFactory->createSimple($variable);
				}
			},
			$variables
		);

		$expression = new Expression($operator, $variables);

		return [$expression, $matches['rest']];
	}

	/**
	 * Parses a literal string from the front of a string.
	 *
	 * @param string $string
	 * The string from which to parse a literal.
	 *
	 * @return array
	 * An array of two values. The first value is the parsed
	 * `\Uri\Template\Parts\Part` instance which represents the expression, or
	 * `false` if the parsing failed. The second value is the string remaining
	 * after parsing, or just `$string` in case of failure.
	 *
	 * @todo
	 * Define a contingency to throw on parse failures.
	 *
	 * @todo
	 * Define a type for parse results which encapsulate a parsed value with a
	 * remainder string.
	 */
	protected function parseLiteral($string) {
		$result = \preg_match("/^(?<literal>(?:{$this->getLiteralCharRegex()})*)(?<rest>\X*)$/u", $string, $matches);
		if (!$result || !\strlen($matches['literal'])) {
			return [false, $string];
		}

		return [new Literal($this->characterTypes, $matches['literal']), $matches['rest']];
	}

	/**
	 * Gets a regex implementing the <varspec> rule.
	 *
	 * @return string
	 * A regex string which matches a variable specification.
	 */
	protected function getVarSpecRegex() {
		// TODO Permit level-4 modifier.
		return "{$this->getVarNameRegex()}(?:{$this->getLevel4ModifierRegex()})?";
	}

	/**
	 * Gets a regex implementing the <modifier-level4> rule.
	 *
	 * @return string
	 * A regex string which matches a level 4 expression modifier.
	 */
	protected function getLevel4ModifierRegex() {
		return "{$this->getPrefixRegex()}|{$this->getExplodeRegex()}";
	}

	/**
	 * Gets a regex implementing the <prefix> rule.
	 *
	 * @return string
	 * A regex string which matches a value prefix component.
	 */
	protected function getPrefixRegex() {
		// Colon followed by positive integer < 10000.
		return ":[1-9][0-9]{0,3}";
	}

	/**
	 * Gets a regex implementing the <explode> rule.
	 *
	 * @return string
	 * A regex string which matches a value explosion component.
	 */
	protected function getExplodeRegex() {
		return "\\*";
	}

	/**
	 * Gets a regex implementing the <varname> rule.
	 *
	 * @return string
	 * A regex string which matches a variable name.
	 */
	protected function getVarNameRegex() {
		$varChar = $this->getVarCharRegex();
		return "$varChar(?:\\.?$varChar)*";
	}

	/**
	 * Gets a regex implementing the <varchar> rule.
	 *
	 * @return string
	 * A regex string which matches a variable character.
	 */
	protected function getVarCharRegex() {
		return "(?:[A-Za-z0-9_]|{$this->getPercentEncodedRegex()})";
	}

	/**
	 * Gets a regex implementing the <literals> rule.
	 *
	 * @return string
	 * A regex string which matches a literal character.
	 */
	protected function getLiteralCharRegex() {
		return '[!#$&()*+,\\-.\\/0-9:;=?@A-Z[\\]_a-z~]'
			."|(?:{$this->characterTypes->ucschar})|(?:{$this->characterTypes->iprivate})"
			."|(?:{$this->getPercentEncodedRegex()})";
	}

	/**
	 * Gets a regex implementing the <pct-encoded> rule.
	 *
	 * @return string
	 * A regex string which matches a percent encoded character.
	 */
	protected function getPercentEncodedRegex() {
		return "%{$this->characterTypes->hexDigit}{2}";
	}
}
?>