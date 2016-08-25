<?php
use PHPUnit\Framework\TestCase;

use Uri\Lexical\CharacterTypes;
use Uri\Lexical\CharacterType;
use Uri\Lexical\RegexCharacterType;
use Uri\Lexical\UnionCharacterType;

class LexicalTest extends TestCase
{
	public function testCharacterTypes() {
		$charTypes = new CharacterTypes;

		$this->assertFalse(isset($charTypes->test));

		$charTypes->test = new RegexCharacterType('[A-Z]');

		$this->assertTrue(isset($charTypes->test));

		unset($charTypes->test);

		$this->assertFalse(isset($charTypes->test));
	}

	/**
	 * @dataProvider basicCharTypeProvider
	 */
	public function testCharacterTypeContains(CharacterType $charType, $char, $belongs)
	{
		$this->assertEquals($belongs, $charType->contains($char));
	}

	/**
	 * @dataProvider basicCharTypeProvider
	 */
	public function testCharacterTypeRegexMatch(CharacterType $charType, $char, $belongs) {
		$this->assertEquals($belongs, \preg_match("/{$charType->getRegexFragment()}/u", $char));
	}

	/**
	 * @dataProvider unionProvider
	 */
	public function testCharacterBelongsToUnion(UnionCharacterType $union, $char, $expected)
	{
		$this->assertEquals($expected, $union->contains($char));
	}

	public function basicCharTypeProvider() {
		foreach ($this->rawRegexProvider() as list($regex, $char, $expected)) {
			yield [ new RegexCharacterType($regex), $char, $expected ];
		}
	}

	protected function rawRegexProvider() {
		$regex = '[A-Za-z0-9_]';

		return [
			[ $regex, 'a', true ],
			[ $regex, 'b', true ],
			[ $regex, 'c', true ],
			[ $regex, 'x', true ],
			[ $regex, 'y', true ],
			[ $regex, 'z', true ],
			[ $regex, 'A', true ],
			[ $regex, 'B', true ],
			[ $regex, 'C', true ],
			[ $regex, 'X', true ],
			[ $regex, 'Y', true ],
			[ $regex, 'Z', true ],
			[ $regex, '0', true ],
			[ $regex, '1', true ],
			[ $regex, '2', true ],
			[ $regex, '3', true ],
			[ $regex, '4', true ],
			[ $regex, '5', true ],
			[ $regex, '6', true ],
			[ $regex, '7', true ],
			[ $regex, '8', true ],
			[ $regex, '9', true ],
			[ $regex, '_', true ],
		];
	}

	public function unionProvider() {
		foreach ($this->rawUnionProvider() as list($lhsRegex, $rhsRegex, $char, $expected)) {
			yield [
				new UnionCharacterType(
					new RegexCharacterType($lhsRegex),
					new RegexCharacterType($rhsRegex)
				),
				$char,
				$expected
			];
		}
	}

	protected function rawUnionProvider() {
		return [
			[ '[a-z]', '[0-9]', 'a', true ],
			[ '[a-z]', '[0-9]', '5', true ],
			[ '[a-z]', '[0-9]', 'A', false ],
			[ '[a-z]', '[0-9]', '_', false ],
			[ '\\w', '[^\s\S]', 'a', true ],
			[ '\\w', '[^\s\S]', '_', true ],
			[ '\\w', '[^\s\S]', '-', false ],
		];
	}
}
?>