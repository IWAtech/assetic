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
    Assetic\Asset\FileAsset,
    Assetic\Factory\AssetFactory,
    Assetic\Filter\DirectiveProcessor\Parser,
    Assetic\Filter\DirectiveProcessor\Directive,
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
 * Directives must be in the Header of the Source File to be picked up.
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

    /**
     * List of processed files, to avoid following circular references
     * @var array
     */
    protected $processed = array();

    function __construct(Parser $parser = null)
    {
        if (null === $parser) {
            $parser = new Parser;
        }
        $this->parser = $parser;

        $this->register(new RequireDirective);
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
        $newContent = '';

        foreach ($tokens as $token) {
            list($type, $content, $line) = $token;

            if ($type !== Parser::T_DIRECTIVE) {
                $newContent .= $content . "\n";

            } else {
                // TODO: Split by Shell Argument Rules
                $argv = explode(' ', $content);
                $directive = array_shift($argv);

                if (!$this->isRegistered($directive)) {
                    throw new \RuntimeException(sprintf(
                        "Undefined Directive \"%s\" in %s on line %d",
                        $directive,
                        $asset->getSourceRoot() . DIRECTORY_SEPARATOR . $asset->getSourcePath(),
                        $line
                    ));
                }

                $directiveInstance = $this->directives[$directive];
                $filteredAsset = $directiveInstance->execute($asset, $argv);

                if ($filteredAsset instanceof AssetInterface) {
                    $filteredAsset->load();
                    $newContent .= $filteredAsset->getContent() . "\n";
                }
            }
        }
        $this->processed[] = $asset->getSourceRoot() . '/' . $asset->getSourcePath();
        $asset->setContent($newContent);
    }

    /**
     * Checks if the source file has been processed
     */
    function hasProcessed($sourceFile)
    {
        return in_array($sourceFile, $this->processed);
    }
}
