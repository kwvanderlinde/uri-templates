<?php
namespace Uri;

use \Uri\Lexical\CharacterType;
use \Uri\Lexical\UnionCharacterType;

/**
 * Checks whether an array is a sequential numeric array.
 *
 * An array is considered sequential if it is empty or its keys are consecutive
 * integers running from `0` to `count($array) - 1` in that order.
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
 * Rather the percent encoding every character in the string, we only percent
 * encode character which are not considered "safe". The set of safe characters
 * is determined by the `$safeTypes` parameter.
 *
 * If any characters are already percent encoded, they may be kept as-is by
 * setting the `$keepEncoded` parameter to `true`. So, for instance, if a
 * percent-encoded space (`'%20'`) is encoutered, setting `$keepEncoded` to
 * `true` will cause the result to have `'%20'`, rather than, say, `'%2520'`.
 *
 * @param string $string
 * The string which will be percent encoded.
 *
 * @param bool $keepEncoded
 * Whether or not to keep percent encoded characters as-is.
 *
 * @param \Uri\Lexical\CharacterType ...$safeTypes
 * The set of character types which are considered "safe" for the expansion.
 * These characters will not be percent encoded.
 *
 * @return string
 * A percent-encoded string equivalent to `$string`.
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