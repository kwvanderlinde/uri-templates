<?php
namespace Uri\Template;

class ValueDispatcher {
	public function handle($value, $defaultValue, array $handlers) {
		if (\is_null($value)) {
			return $defaultValue;
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
			return $defaultValue;
		}

		return \call_user_func_array($handler, $args);
	}
}
?>