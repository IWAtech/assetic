<?php
/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Filter;

use Assetic\Asset\AssetInterface,
    Assetic\Asset\StringAsset,
    Assetic\Filter\DirectiveProcessor\Parser,
    Assetic\Filter\DirectiveProcessor\Directive,
    Assetic\Filter\DirectiveProcessor\DependOnDirective,
    Assetic\Filter\DirectiveProcessor\RequireDirective;

/**
 * A Filter which processes special directive comments.
 *
 * Directive comments start with the comment prefix and are then
 * followed by an "=". 
 *
 * For example:
 *
 * // Javascript:
 * //= require "foo"
 *
 * # Coffeescript:
 * #= require "foo"
 * 
 * / * CSS
 *   *= require "foo"
 *   * /
 * ( ^ This space must be here, otherwise PHP triggers an Parse Error)
 *
 * Directives must be in the Header of the Source File for getting picked up.
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class DirectiveProcessor implements FilterInterface
{
    /**
     * @var \Assetic\Filter\DirectiveProcessor\Parser
     */
    protected $parser;

    /**
     * Map of available directives
     * @var array
     */
    protected $directives;

    function __construct(Parser $parser = null)
    {
        if (null === $parser) {
            $parser = new Parser;
        }
        $this->parser = $parser;

        $this->register(new RequireDirective);
        $this->register(new DependOnDirective);
    }

    /**
     * Checks if the Directive is registered
     *
     * @param string $name
     * @return bool
     */
    function isRegistered($name)
    {
        return isset($this->directives[$name]);
    }

    /**
     * Register a directive
     *
     * @param Directive $directive
     * @return DirectiveProcessor
     */
    function register(Directive $directive)
    {
        $name = $directive->getName();

        if (empty($name)) {
            throw new \UnexpectedValueException(sprintf(
                "No Name found for Directive %s, please return the Name with the
                Directive's getName() Method",
                get_class($directive)
            ));
        }

        $directive->setProcessor($this);
        $this->directives[$name] = $directive;

        return $this;
    }

    function filterDump(AssetInterface $asset)
    {}
    
    function filterLoad(AssetInterface $asset)
    {
        $tokens = $this->parser->parse($asset->getContent());
        $newSource = '';

        foreach ($tokens as $token) {
            list($type, $content, $line) = $token;

            if ($type !== Parser::T_DIRECTIVE) {
                $newSource .= $content . "\n";

            } else {
                // TODO: Split by Shell Argument Rules
                $argv = explode(' ', $content);
                $directive = array_shift($argv);

                if ($this->isRegistered($directive)) {
					$directiveInstance = $this->directives[$directive];
					$directiveInstance->execute($asset, $argv);
                }
            }
        }

        $asset->setContent($newSource);
    }
}
