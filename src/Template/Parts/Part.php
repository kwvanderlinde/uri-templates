<?php
namespace Uri\Template\Parts;

interface Part {
	function expand(array $variables);
}
?>