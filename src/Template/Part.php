<?php
namespace Uri\Template;

interface Part {
	function expand(array $variables);
}
?>