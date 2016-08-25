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
		'keys_some_undef' => [ 'semi' => ';', 'dot' => null, 'comma' => ',' ],
		'keys_all_undef' => [ 'semi' => null, 'dot' => null, 'comma' => null ],
		'list' => [ 'red', 'green', 'blue' ],
		'list_some_undef' => [ 'red', null, 'blue' ],
		'list_all_undef' => [ null, null, null ],
		'path' => '/foo/bar',
		'undef' => null,
		'var' => 'value',
		'v' => '6',
		'x' => '1024',
		'y' => '768',
		'who' => 'fred'
	];

	/**
	 * @dataProvider templateStringsAndExpansions
	 */
	public function testTemplate($templateString, $expected)
	{
		$parser = new \Uri\Template\Parser();
		$template = $parser->parse($templateString);
		$result = $template->expand($this->variables);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider templateStrings
	 */
	public function testParser($templateString, $expectedException = null) {
		if (!\is_null($expectedException)) {
			$this->expectException($expectedException);
		}

		$parser = new \Uri\Template\Parser();
		$template = $parser->parse($templateString);
	}

	public function templateStrings() {
		// Give all the strings from templateStringsAndExpansions.
		foreach ($this->templateStringsAndExpansions() as list($string, $_)) {
			yield [$string];
		}

		foreach ($this->malformedTemplateStrings() as $string) {
			yield [ $string, \Base\Exceptions\LogicError::class ];
		}
	}

	public function malformedTemplateStrings() {
		return [
			'{var',
			'{x,$y}',
			'/bad/encoded/%ZF/here',
			'/bad/char/"',
		];
	}

	public function templateStringsAndExpansions()
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
			[ '{list_some_undef}', 'red,blue' ],
			[ '{list_all_undef}', '' ],
			[ '{keys}', 'semi,%3B,dot,.,comma,%2C' ],
			[ '{keys_some_undef}', 'semi,%3B,comma,%2C' ],
			[ '{keys_all_undef}', '' ],

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
			[ '{+list_some_undef}', 'red,blue' ],
			[ '{+list_all_undef}', '' ],
			[ '{+keys}', 'semi,;,dot,.,comma,,' ],
			[ '{+keys_some_undef}', 'semi,;,comma,,' ],
			[ '{+keys_all_undef}', '' ],
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
			[ 'X{.list_some_undef}', 'X.red,blue' ],
			[ 'X{.list_all_undef}', 'X' ],
			[ 'X{.keys}', 'X.semi,%3B,dot,.,comma,%2C' ],
			[ 'X{.keys_some_undef}', 'X.semi,%3B,comma,%2C' ],
			[ 'X{.keys_all_undef}', 'X' ],
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
			[ '{/list_some_undef}', '/red,blue' ],
			[ '{/list_all_undef}', '' ],
			[ '{/keys}', '/semi,%3B,dot,.,comma,%2C' ],
			[ '{/keys_some_undef}', '/semi,%3B,comma,%2C' ],
			[ '{/keys_all_undef}', '' ],
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
			[ '{;list_some_undef}', ';list_some_undef=red,blue' ],
			[ '{;list_all_undef}', '' ],
			[ '{;keys}', ';keys=semi,%3B,dot,.,comma,%2C' ],
			[ '{;keys_some_undef}', ';keys_some_undef=semi,%3B,comma,%2C' ],
			[ '{;keys_all_undef}', '' ],
			// Form-style query
			[ '{?who}', '?who=fred' ],
			[ '{?half}', '?half=50%25' ],
			[ '{?x,y}', '?x=1024&y=768' ],
			[ '{?x,y,empty}', '?x=1024&y=768&empty=' ],
			[ '{?x,y,undef}', '?x=1024&y=768' ],
			[ '{?var}', '?var=value' ],
			[ '{?list}', '?list=red,green,blue' ],
			[ '{?list_some_undef}', '?list_some_undef=red,blue' ],
			[ '{?list_all_undef}', '' ],
			[ '{?keys}', '?keys=semi,%3B,dot,.,comma,%2C' ],
			[ '{?keys_some_undef}', '?keys_some_undef=semi,%3B,comma,%2C' ],
			[ '{?keys_all_undef}', '' ],
			// Form-style query continuation
			[ '{&who}', '&who=fred' ],
			[ '{&half}', '&half=50%25' ],
			[ '?fixed=yes{&x}', '?fixed=yes&x=1024' ],
			[ '{&x,y}', '&x=1024&y=768' ],
			[ '{&x,y,empty}', '&x=1024&y=768&empty=' ],
			[ '{&x,y,undef}', '&x=1024&y=768' ],
			[ '{&var}', '&var=value' ],
			[ '{&list}', '&list=red,green,blue' ],
			[ '{&list_some_undef}', '&list_some_undef=red,blue' ],
			[ '{&list_all_undef}', '' ],
			[ '{&keys}', '&keys=semi,%3B,dot,.,comma,%2C' ],
			[ '{&keys_some_undef}', '&keys_some_undef=semi,%3B,comma,%2C' ],
			[ '{&keys_all_undef}', '' ],

			// Level 4
			// Simple
			[ '{var:3}', 'val' ],
			[ '{var:30}', 'value' ],
			[ '{list*}', 'red,green,blue' ],
			[ '{list_some_undef*}', 'red,blue' ],
			[ '{list_all_undef*}', '' ],
			[ '{keys*}', 'semi=%3B,dot=.,comma=%2C' ],
			[ '{keys_some_undef*}', 'semi=%3B,comma=%2C' ],
			[ '{keys_all_undef*}', '' ],
			// Reserved
			[ '{+path:6}/here', '/foo/b/here' ],
			[ '{+list*}', 'red,green,blue' ],
			[ '{+list_some_undef*}', 'red,blue' ],
			[ '{+list_all_undef*}', '' ],
			[ '{+keys*}', 'semi=;,dot=.,comma=,' ],
			[ '{+keys_some_undef*}', 'semi=;,comma=,' ],
			[ '{+keys_all_undef*}', '' ],
			// Label
			[ 'X{.var:3}', 'X.val' ],
			[ 'www{.dom*}', 'www.example.com' ],
			[ 'X{.list*}', 'X.red.green.blue' ],
			[ 'X{.list_some_undef*}', 'X.red.blue' ],
			[ 'X{.list_all_undef*}', 'X' ],
			[ 'X{.keys*}', 'X.semi=%3B.dot=..comma=%2C' ],
			[ 'X{.keys_some_undef*}', 'X.semi=%3B.comma=%2C' ],
			[ 'X{.keys_all_undef*}', 'X' ],
			[ 'X{.empty_keys*}', 'X' ],
			// Path segment
			[ '{/var:1,var}', '/v/value' ],
			[ '{/list*}', '/red/green/blue' ],
			[ '{/list_some_undef*}', '/red/blue' ],
			[ '{/list_all_undef*}', '' ],
			[ '{/list*,path:4}', '/red/green/blue/%2Ffoo' ],
			[ '{/list_some_undef*,path:4}', '/red/blue/%2Ffoo' ],
			[ '{/list_all_undef*,path:4}', '/%2Ffoo' ],
			[ '{/keys*}', '/semi=%3B/dot=./comma=%2C' ],
			[ '{/keys_some_undef*}', '/semi=%3B/comma=%2C' ],
			[ '{/keys_all_undef*}', '' ],
			// Path-style parameter
			[ '{;hello:5}', ';hello=Hello' ],
			[ '{;hello:7}', ';hello=Hello%20W' ],
			[ '{;list*}', ';list=red;list=green;list=blue' ],
			[ '{;list_some_undef*}', ';list_some_undef=red;list_some_undef=blue' ],
			[ '{;list_all_undef*}', '' ],
			[ '{;keys*}', ';semi=%3B;dot=.;comma=%2C' ],
			[ '{;keys_some_undef*}', ';semi=%3B;comma=%2C' ],
			[ '{;keys_all_undef*}', '' ],
			// Form-style query
			[ '{?var:3}', '?var=val' ],
			[ '{?list*}', '?list=red&list=green&list=blue' ],
			[ '{?list_some_undef*}', '?list_some_undef=red&list_some_undef=blue' ],
			[ '{?list_all_undef*}', '' ],
			[ '{?keys*}', '?semi=%3B&dot=.&comma=%2C' ],
			[ '{?keys_some_undef*}', '?semi=%3B&comma=%2C' ],
			[ '{?keys_all_undef*}', '' ],
			// Form-style query continuation
			[ '{&var:3}', '&var=val' ],
			[ '{&list*}', '&list=red&list=green&list=blue' ],
			[ '{&list_some_undef*}', '&list_some_undef=red&list_some_undef=blue' ],
			[ '{&list_all_undef*}', '' ],
			[ '{&keys*}', '&semi=%3B&dot=.&comma=%2C' ],
			[ '{&keys_some_undef*}', '&semi=%3B&comma=%2C' ],
			[ '{&keys_all_undef*}', '' ],
			// Test prefixing composite values.
			[ '{list:4}', 'red,green,blue' ],
			[ '{list_some_undef:4}', 'red,blue' ],
			[ '{list_all_undef:4}', '' ],
			[ '{keys:5}', 'semi,%3B,dot,.,comma,%2C' ],
			[ '{keys_some_undef:5}', 'semi,%3B,comma,%2C' ],
			[ '{keys_all_undef:5}', '' ],
		];
	}
}
?>