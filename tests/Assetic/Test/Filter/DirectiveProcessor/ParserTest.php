<?php

namespace Assetic\Test\Filter\DirectiveProcessor;

use Assetic\Filter\DirectiveProcessor\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    function setUp()
    {
        $this->parser = new Parser;
    }

    function testParse()
    {
        $source = file_get_contents(__DIR__ . "/../fixtures/directiveprocessor/test1.js");
        $tokens = $this->parser->parse($source);

        $this->assertEquals(11, count($tokens));

        $directives = array_reduce($tokens, function($sum, $token) {
            list($t, $line) = $token;

            if (Parser::T_DIRECTIVE == $t) {
                $sum++;
            }
            return $sum;
        }, 0);

        $this->assertEquals(4, $directives);
    }

    function testNoCodeFile()
    {
        $tokens = $this->parser->parse(
            file_get_contents(__DIR__ . "/../fixtures/directiveprocessor/no_code.js")
        );

        $this->assertEquals(2, count($tokens));
    }
}
