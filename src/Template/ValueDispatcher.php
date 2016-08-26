<?php
namespace Uri\Template;

/**
 * Determines the type of a value, and permits dispatching on its type.
 *
 * Only three types of values are recognized: `null`; `string`; and `array`.
 * These map to the terms 'undefined' (For `null`), 'string' (For `string`),
 * 'list' (for sequential `array`s), and 'array' (for associative arrays).
 *
 */
class ValueDispatcher {
	/**
	 * Dispatches on a value's type.
	 *
	 * @param mixed value
	 * The value to dispatch on.
	 *
	 * @param mixed defaultResult
	 * If `$value` is `null`, or no handler is available for `$value`, then this
	 * is the value that will be returned.
	 *
	 * @param callable[] $handlers
	 * An array of callbacks. The keys are the types which each handler can
	 * handle. The first parameter to each handler is the `$value`, converted to
	 * its handleable type. For arrays, a second parameter is provided which is
	 * `true` if the array is sequential, or false otherwise.
	 *
	 * @return mixed
	 * The result of applying a handler to `$value`, or `$defaultValue` if
	 * `$value` is `null` or no appropriate handler is available.
	 *
	 * @todo
	 * Make `$value` into a property of `$this` rather than a parameter of this
	 * method.
	 *
	 * @todo
	 * Remove the second parameter to array handlers. Instead, we ought to
	 * provide two different keys, `list` and `assoc` for sequential and
	 * non-sequential arrays, respectively. We could keep the key `array` as an
	 * alternative to specifying the `list` and `assoc` handlers separately, in
	 * case the logic for the two is quite similar.
	 */
	public function handle($value, $defaultResult, array $handlers) {
		if (\is_null($value)) {
			return $defaultResult;
		}

		if (\is_array($value)) {
			$handlerKey = 'array';
			$args = [ $value, \Uri\isSequentialArray($value) ];
		}
		else {
			$handlerKey = 'string';
			$args = [ (string)$value ];
		}

		$handler = @$handlers[$handlerKey];
		if (\is_null($handler)) {
			return $defaultResult;
		}

		return \call_user_func_array($handler, $args);
	}
}
?>