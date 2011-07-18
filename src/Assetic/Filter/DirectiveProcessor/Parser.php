<?php

namespace Assetic\Filter\DirectiveProcessor;

/**
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class Parser
{
    const T_ML_COMMENT_START = "/*";
    const T_ML_COMMENT = "*";
    const T_ML_COMMENT_END = "*/";
    const T_COMMENT = "//";
    const T_DIRECTIVE = '=';
    const T_CONTENT = 2;

    /**
     * Source Code to parse
     * @var string
     */
    protected $source;

    function __construct($source)
    {
        $this->source = $source;
    }

    /**
     * Parses the Source Code
     *
     * @return array Stream of Tokens
     */
    function parse()
    {
        $header = true;
        $source = trim($this->source);
        $tokens = array();

        do {
            $pos = strpos($source, "\n");
            $line = trim(substr($source, 0, $pos));

            if ('' === $line) {
                
            } else if (self::T_ML_COMMENT_START == substr($line, 0, 2)) {
                $tokens[] = array(self::T_ML_COMMENT_START, $line);

            } else if (self::T_ML_COMMENT_END == substr($line, 0, 2)) {
                $tokens[] = array(self::T_ML_COMMENT_END, $line);

            } else if (self::T_COMMENT == substr($line, 0, 2)) {
                $comment = array(self::T_COMMENT, ltrim(substr($line, 2)));

            } else if (self::T_ML_COMMENT == $line[0]) {
                $comment = array(self::T_ML_COMMENT, ltrim(substr($line, 1)));

            // Directives are only picked up before any code.
            // We are not in the header anymore if something other than 
            // white space is coming up
            } else if ('' !== rtrim($line)) {
                break;
            }

            // Look for directives in comment bodys
            if (!empty($comment)) {
                list ($token, $content) = $comment;

                if (self::T_DIRECTIVE === $content[0]) {
                    $tokens[] = array(self::T_DIRECTIVE, substr($content, 1));
                } else {
                    $tokens[] = $comment;
                }
                unset($comment);
            }

            if (false === $pos) {
                $header = false;
            }
            if (empty($source)) {
                $header = false;
            }
            $source = substr($source, $pos + 1);

        } while (true === $header);

        $tokens[] = array(self::T_CONTENT, $source);
        return $tokens;
    }
}
