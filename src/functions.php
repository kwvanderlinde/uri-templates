<?php
namespace Uri;

use \Uri\Lexical\CharacterType;
use \Uri\Lexical\UnionCharacterType;

/**
 * Checks whether an array is a sequential numeric array.
 *
 * @param array $array
 * The array to check.
 *
 * @return bool
 * `true` if the array is sequential and numeric. Otherwise, `false`.
 */
function isSequentialArray(array $array) {
	return !$array || (\array_keys($array) === \range(0, \count($array) - 1));
}

/**
 * Percent encodes a string.
 *
 * @param string $string
 * The string to encode.
 *
 * @param bool $keepEncoded
 * If `true`, existing percent encoded characters will be kept as is. Otherwise,
 * any "%" characters will be percent encoded to %25 (unless it belongs to one
 * of `$safeTypes`).
 *
 * @param CharacterType $safeTypes
 * The set of character types which are considered "safe" for the expansion.
 * These characters will not be percent encoded.
 */
function percentEncode($string, $keepEncoded, CharacterType... $safeTypes) {
	// Combine the allowed types.
	$charType = new UnionCharacterType(...$safeTypes);

	$result = '';
	for ($i = 0, $n = \strlen($string); $i < $n; ++$i) {
		$char = $string[$i];

		if ($keepEncoded && $char === '%') {
			$encoded = \substr($string, $i, 3);
			// Is `$encoded` a percent encoded string?
			if (\preg_match('/%[0-9A-Za-z]{2}/u', $encoded)) {
				$result .= $encoded;
				$i += 2;
				continue;
			}
		}

		// Either not a percent encoded character, `!$keepEncoded`.
		if ($charType->contains($char)) {
			// Output as is.
			$result .= $char;
			continue;
		}

		// Default action: percent encode the character.
		$octets = unpack("C*", $char);
		$encodedOctets = \array_map(
			static function ($int) {
				$string = \strtoupper(\dechex($int));
				while (\strlen($string) < 2) {
					$string = '0'.$string;
				}
				return '%'.$string;
			},
			$octets
		);
		$result .= \implode('', $encodedOctets);
	}

	return $result;
}
?>