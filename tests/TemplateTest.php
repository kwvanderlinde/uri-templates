<?php
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
	private $variables = [
		'base' => 'http://example.com/home/',
		'count' => ['one', 'two', 'three'],
		'dom' => ['example', 'com'],
		'dub' => 'me/too',
		'empty' => '',
		'empty_keys' => [],
		'half' => '50%',
		'hello' => 'Hello World!',
		'keys' => [ 'semi' => ';', 'dot' => '.', 'comma' => ',' ],
		'list' => ['red', 'green', 'blue'],
		'path' => '/foo/bar',
		'undef' => null,
		'var' => 'value',
		'v' => '6',
		'x' => '1024',
		'y' => '768',
		'who' => 'fred'
	];

	/**
	 * @dataProvider templateStrings
	 */
	public function testTemplate($templateString, $expected)
	{
		$parser = new \Uri\Template\Parser();
		$template = $parser->parse($templateString);
		$result = $template->expand($this->variables);
		$this->assertEquals($expected, $result);
	}

	public function templateStrings()
	{
		return [
			// Simple string expansion.
			[ '{var}', 'value' ],
			[ '{hello}', 'Hello%20World%21' ],
			[ '{half}', '50%25' ],
			[ 'O{empty}X', 'OX' ],
			[ 'O{undef}X', 'OX' ],
			[ '{x,y}', '1024,768' ],
			[ '{x,hello,y}', '1024,Hello%20World%21,768' ],
			[ '?{x,empty}', '?1024,' ],
			[ '?{x,undef}', '?1024' ],
			[ '?{undef,y}', '?768' ],
			[ '{list}', 'red,green,blue' ],
			[ '{keys}', 'semi,%3B,dot,.,comma,%2C' ],

			// Level 2 string expansion.
			// Reserved
			[ '{+var}', 'value' ],
			[ '{+hello}', 'Hello%20World!' ],
			[ '{+half}', '50%25' ],
			[ '{base}index', 'http%3A%2F%2Fexample.com%2Fhome%2Findex' ],
			[ '{+base}index', 'http://example.com/home/index' ],
			[ 'O{+empty}X', 'OX' ],
			[ 'O{+undef}X', 'OX' ],
			[ '{+path}/here', '/foo/bar/here' ],
			[ 'here?ref={+path}', 'here?ref=/foo/bar' ],
			[ 'up{+path}{var}/here', 'up/foo/barvalue/here' ],
			[ '{+x,hello,y}', '1024,Hello%20World!,768' ],
			[ '{+path,x}/here', '/foo/bar,1024/here' ],
			[ '{+list}', 'red,green,blue' ],
			[ '{+keys}', 'semi,;,dot,.,comma,,' ],
			// Fragment
			[ 'X{#var}', 'X#value' ],
			[ 'X{#hello}', 'X#Hello%20World!' ],

			// Level 3 string expansion.
			// Label
			[ '{.who}', '.fred' ],
			[ '{.who,who}', '.fred.fred' ],
			[ '{.half,who}', '.50%25.fred' ],
			[ 'X{.var}', 'X.value' ],
			[ 'X{.empty}', 'X.' ],
			[ 'X{.undef}', 'X' ],
			[ 'X{.list}', 'X.red,green,blue' ],
			[ 'X{.keys}', 'X.semi,%3B,dot,.,comma,%2C' ],
			[ 'X{.empty_keys}', 'X' ],
			// Path segment
			[ '{/who}', '/fred' ],
			[ '{/who,who}', '/fred/fred' ],
			[ '{/half,who}', '/50%25/fred' ],
			[ '{/who,dub}', '/fred/me%2Ftoo' ],
			[ '{/var}', '/value' ],
			[ '{/var,empty}', '/value/' ],
			[ '{/var,undef}', '/value' ],
			[ '{/var,x}/here', '/value/1024/here' ],
			[ '{/list}', '/red,green,blue' ],
			[ '{/keys}', '/semi,%3B,dot,.,comma,%2C' ],
			// Path-style parameters
			[ '{;who}', ';who=fred' ],
			[ '{;half}', ';half=50%25' ],
			[ '{;empty}', ';empty' ],
			[ '{;v,empty,who}', ';v=6;empty;who=fred' ],
			[ '{;v,bar,who}', ';v=6;who=fred' ],
			[ '{;x,y}', ';x=1024;y=768' ],
			[ '{;x,y,empty}', ';x=1024;y=768;empty' ],
			[ '{;x,y,undef}', ';x=1024;y=768' ],
			[ '{;list}', ';list=red,green,blue' ],
			[ '{;keys}', ';keys=semi,%3B,dot,.,comma,%2C' ],
			// Form-style query
			[ '{?who}', '?who=fred' ],
			[ '{?half}', '?half=50%25' ],
			[ '{?x,y}', '?x=1024&y=768' ],
			[ '{?x,y,empty}', '?x=1024&y=768&empty=' ],
			[ '{?x,y,undef}', '?x=1024&y=768' ],
			[ '{?var}', '?var=value' ],
			[ '{?list}', '?list=red,green,blue' ],
			[ '{?keys}', '?keys=semi,%3B,dot,.,comma,%2C' ],
			// Form-style query continuation
			[ '{&who}', '&who=fred' ],
			[ '{&half}', '&half=50%25' ],
			[ '?fixed=yes{&x}', '?fixed=yes&x=1024' ],
			[ '{&x,y}', '&x=1024&y=768' ],
			[ '{&x,y,empty}', '&x=1024&y=768&empty=' ],
			[ '{&x,y,undef}', '&x=1024&y=768' ],
			[ '{&var}', '&var=value' ],
			[ '{&list}', '&list=red,green,blue' ],
			[ '{&keys}', '&keys=semi,%3B,dot,.,comma,%2C' ],

			// Level 4
			// Simple
			[ '{var:3}', 'val' ],
			[ '{var:30}', 'value' ],
			[ '{list*}', 'red,green,blue' ],
			[ '{keys*}', 'semi=%3B,dot=.,comma=%2C' ],
			// Reserved
			[ '{+path:6}/here', '/foo/b/here' ],
			[ '{+list*}', 'red,green,blue' ],
			[ '{+keys*}', 'semi=;,dot=.,comma=,' ],
			// Label
			[ 'X{.var:3}', 'X.val' ],
			[ 'www{.dom*}', 'www.example.com' ],
			[ 'X{.list*}', 'X.red.green.blue' ],
			[ 'X{.keys*}', 'X.semi=%3B.dot=..comma=%2C' ],
			[ 'X{.empty_keys*}', 'X' ],
			// Path segment
			[ '{/var:1,var}', '/v/value' ],
			[ '{/list*}', '/red/green/blue' ],
			[ '{/list*,path:4}', '/red/green/blue/%2Ffoo' ],
			[ '{/keys*}', '/semi=%3B/dot=./comma=%2C' ],
			// Path-style parameter
			[ '{;hello:5}', ';hello=Hello' ],
			[ '{;hello:7}', ';hello=Hello%20W' ],
			[ '{;list*}', ';list=red;list=green;list=blue' ],
			[ '{;keys*}', ';semi=%3B;dot=.;comma=%2C' ],
			// Form-style query
			[ '{?var:3}', '?var=val' ],
			[ '{?list*}', '?list=red&list=green&list=blue' ],
			[ '{?keys*}', '?semi=%3B&dot=.&comma=%2C' ],
			// Form-style query continuation
			[ '{&var:3}', '&var=val' ],
			[ '{&list*}', '&list=red&list=green&list=blue' ],
			[ '{&keys*}', '&semi=%3B&dot=.&comma=%2C' ],
			// Test prefixing composite values.
			[ '{list:4}', 'red,green,blue' ],
			[ '{keys:5}', 'semi,%3B,dot,.,comma,%2C' ],
		];
	}
}
?>