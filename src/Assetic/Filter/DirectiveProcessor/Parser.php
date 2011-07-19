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
    const T_ALT_COMMENT = "#";
    const T_DIRECTIVE = '=';
    const T_CONTENT = 2;

    /**
     * Parses the Source Code
     *
     * @return array Stream of Tokens
     */
    function parse($source)
    {
        $source = (string) $source;

        if (empty($source)) {
            throw new \InvalidArgumentException("Source cannot be empty");
        } 

        $header = true;
        $source = trim($source);
        $tokens = array();
        $lineNumber = 1;

        do {
            $pos = strpos($source, "\n");
            $line = trim(substr($source, 0, $pos));

            if ('' === $line) {
                // no op
            } else if (self::T_ML_COMMENT_START == substr($line, 0, 2)) {
                $tokens[] = array(self::T_ML_COMMENT_START, $line, $lineNumber);

            } else if (self::T_ML_COMMENT_END == substr($line, 0, 2)) {
                $tokens[] = array(self::T_ML_COMMENT_END, $line, $lineNumber);

            // Save T_COMMENT, T_ALT_COMMENT and T_ML_COMMENT for later
            // inspection for directives
            } else if (self::T_COMMENT == substr($line, 0, 2)) {
                $comment = array(self::T_COMMENT, $line, $lineNumber);

            } else if (self::T_ALT_COMMENT == substr($line, 0, 2)) {
                $comment = array(self::T_ALT_COMMENT, $line, $lineNumber);

            } else if (self::T_ML_COMMENT == $line[0]) {
                $comment = array(self::T_ML_COMMENT, $line, $lineNumber);

            // Directives are only picked up before any code.
            // We are not in the header anymore if something other than 
            // white space is coming up
            } else if ('' !== rtrim($line)) {
                break;
            }

            // Look for directives in comment bodys
            if (!empty($comment)) {
                list ($token, $content, $n) = $comment;
                $content = ltrim(substr($content, strlen($token)));

                if (self::T_DIRECTIVE === $content[0]) {
                    $tokens[] = array(self::T_DIRECTIVE, trim(substr($content, 1)), $n);
                } else {
                    $tokens[] = $comment;
                }
                unset($comment);
            }

            // Break if no lines remain
            if (false === $pos) {
                break;
            }

            // Break if there isn't any source to process
            if (empty($source)) {
                break;
            }

            ++$lineNumber;
            $source = substr($source, $pos + 1);

        } while (true === $header);

        // Append the remaining Source Code as T_CONTENT
        $tokens[] = array(self::T_CONTENT, $source, $lineNumber);
        return $tokens;
    }
}
