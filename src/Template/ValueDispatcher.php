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
	 * @var mixed
	 * The value to dispatch on.
	 */
	private $value;

	/**
	 * @var mixed
	 * The value to use if `$this->value` is `null`, or no handlers are present
	 * for the value.
	 */
	private $defaultResult;

	/**
	 * Initializes a `ValueDispatcher`.
	 *
	 * @param mixed $value
	 * The value to dispatch on.
	 *
	 * @param mixed $defaultResult
	 * If `$value` is `null`, or no handler is available for `$value`, then this
	 * is the value that will be returned.
	 */
	public function __construct($value, $defaultResult) {
		$this->value = $value;
		$this->defaultResult = $defaultResult;
	}

	/**
	 * Dispatches on a value's type.
	 *
	 * @param callable[] $handlers
	 * An array of callbacks. The keys are the types which each handler can
	 * handle. The first parameter to each handler is the value, converted to
	 * its handleable type. For arrays, a second parameter is provided which is
	 * `true` if the array is sequential, or false otherwise.
	 *
	 * @return mixed
	 * The result of applying a handler to the value, or the default value if
	 * the value is `null` or no appropriate handler is available.
	 */
	public function handle(array $handlers) {
		if (\is_null($this->value)) {
			return $this->defaultResult;
		}

		if (\is_array($this->value)) {
			if (\Uri\isSequentialArray($this->value)) {
				$handlerKey = 'list';
			}
			else {
				$handlerKey = 'assoc';
			}

			$value = $this->value;
		}
		else {
			$handlerKey = 'string';
			$value = (string)$this->value;
		}

		$handler = @$handlers[$handlerKey];
		if (\is_null($handler)) {
			return $this->defaultResult;
		}

		return \call_user_func($handler, $value);
	}
}
?>