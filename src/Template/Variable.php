<?php
namespace Uri\Template;

interface Variable {
	function getName();

	function getPrefixCount();

	function isExploded();

	function getValuePrefix($value);
}
?>