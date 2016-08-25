<?php
namespace Uri\Template\Variables;

interface Variable {
	function getName();

	function getPrefixCount();

	function isExploded();

	function getValuePrefix($value);
}
?>