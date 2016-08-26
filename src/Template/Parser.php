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
	 * @param Operator[] $operators
	 * The operators which are defined for URI templates. The keys are the
	 * operator names, while the values give each operator's semantics.
	 *
	 * @param CharacterTypes $characterTypes
	 * The defined character sets for URI templates.
	 *
	 * @since 1.0.0
	 */
	public function __construct(array $operators, CharacterTypes $characterTypes) {
		$this->characterTypes = $characterTypes;
		$this->operators = $operators;
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
	 *
	 * @since 1.0.0
	 */
	public function parse($templateString) {
		$parts = array();

		$remaining = $templateString;

		while (\strlen($remaining)) {
			try {
				// Either match a literal or an expression,
				if ($remaining[0] === '{') {
					$parser = [ $this, 'parseExpression' ];
				}
				else {
					$parser = [ $this, 'parseLiteral' ];
				}

				$result = \call_user_func($parser, $remaining);

				$newRemaining = $result->getRemainingInput();
				if (\strlen($newRemaining) === \strlen($remaining)) {
					// @codeCoverageIgnoreStart
					throw new LogicError('Parser matched the empty string. This is not supposed to happen as it results in an infinite loop.');
					// @codeCoverageIgnoreEnd
				}
				$remaining = $newRemaining;

				$parts[] = $result->getPayload();
			}
			catch (ParseFailedException $ex) {
				throw new LogicError('Expected expression or literal at '.(\strlen($templateString) - \strlen($remaining))." in '$templateString'");
			}
		}

		return new \Uri\Template(...$parts);
	}

	/**
	 * Parses an expression from the front of a string.
	 *
	 * @param string $string
	 * The string from which to parse an expression.
	 *
	 * @return ParseResult
	 * An object holding the parsed expression and the remaining input.
	 *
	 * @since 1.0.0
	 */
	protected function parseExpression($string) {
		if (!\preg_match("/^\\{(?<operator>{$this->characterTypes->operator})?(?<variables>{$this->getVarSpecRegex()}(?:,{$this->getVarSpecRegex()})*)\\}(?<rest>\X*)/u", $string, $matches)) {
			throw new ParseFailedException(
				'Expected expression',
				$string
			);
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

		return new ParseResult(
			new Expression($operator, $variables),
			$matches['rest']
		);
	}

	/**
	 * Parses a literal string from the front of a string.
	 *
	 * @param string $string
	 * The string from which to parse a literal.
	 *
	 * @return ParseResult
	 * An object holding the parsed literal and the remaining input.
	 *
	 * @since 1.0.0
	 */
	protected function parseLiteral($string) {
		$result = \preg_match("/^(?<literal>(?:{$this->getLiteralCharRegex()})+)(?<rest>\X*)$/u", $string, $matches);
		if (!$result || !\strlen($matches['literal'])) {
			throw new ParseFailedException(
				'Expected literal',
				$string
			);
		}

		return new ParseResult(
			new Literal($this->characterTypes, $matches['literal']),
			$matches['rest']
		);
	}

	/**
	 * Gets a regex implementing the <varspec> rule.
	 *
	 * @return string
	 * A regex string which matches a variable specification.
	 *
	 * @since 1.0.0
	 */
	protected function getVarSpecRegex() {
		return "{$this->getVarNameRegex()}(?:{$this->getLevel4ModifierRegex()})?";
	}

	/**
	 * Gets a regex implementing the <modifier-level4> rule.
	 *
	 * @return string
	 * A regex string which matches a level 4 expression modifier.
	 *
	 * @since 1.0.0
	 */
	protected function getLevel4ModifierRegex() {
		return "{$this->getPrefixRegex()}|{$this->getExplodeRegex()}";
	}

	/**
	 * Gets a regex implementing the <prefix> rule.
	 *
	 * @return string
	 * A regex string which matches a value prefix component.
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
	 */
	protected function getExplodeRegex() {
		return "\\*";
	}

	/**
	 * Gets a regex implementing the <varname> rule.
	 *
	 * @return string
	 * A regex string which matches a variable name.
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
	 */
	protected function getVarCharRegex() {
		return "(?:[A-Za-z0-9_]|{$this->getPercentEncodedRegex()})";
	}

	/**
	 * Gets a regex implementing the <literals> rule.
	 *
	 * @return string
	 * A regex string which matches a literal character.
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
	 */
	protected function getPercentEncodedRegex() {
		return "%{$this->characterTypes->hexDigit}{2}";
	}
}
?>