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
    Assetic\Factory\AssetFactory;

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
     * Matches the File's first comments
     * @const string
     */
    const HEADER_PATTERN = '/
        \A (
            (?m:\s*) (
              (\/\* (?m:.*?) \*\/) |
              (\#\#\# (?m:.*?) \#\#\#) |
              (\/\/ .* \n?)+ |
              (\# .* \n?)+
            )
        )+
    /x';

    /**
     * Matches the directive itself
     */
    const DIRECTIVE_PATTERN = "/
        ^ [\W]* = \s* (\w+.*?) (\*\/)? $
    /x";

    protected $includedFiles = array();
    protected $cwd;

    function filterDump(AssetInterface $asset)
    {}
    
    function filterLoad(AssetInterface $asset)
    {
        $this->includedFiles = array();
        $this->cwd = $asset->getSourceRoot();

        $source = $asset->getContent();
        
        $count = preg_match(self::HEADER_PATTERN, $source, $matches);
        $header = $count ? $matches[0] : '';

        $directives = $this->parseDirectives($header);
        $this->processDirectives($directives);

        $newSource = '';

        print_r($this->includedFiles);
	foreach ($this->includedFiles as $path) {
	    var_dump($this->cwd);
	    $path = realpath($this->cwd . DIRECTORY_SEPARATOR . $path);

            $file = new FileAsset($path, $asset->getFilters());
            $file->load();
            $newSource .= $file->getContent();
        }
        $newSource .= $source;
        $asset->setContent($newSource);
    }

    protected function processRequireDirective($path)
    {
        if (!$this->isRelative($path)) {
            $path = './' . $path;
        }

        if (!in_array($path, $this->includedFiles)) {
            $this->includedFiles[] = $path;
        }
    }

    protected function processDirectives(array $directives)
    {
        foreach ($directives as $directive) {
            $i    = $directive[0];
            $cmd  = $directive[1];
            $args = array_slice($directive, 2);

            $method = ucwords(str_replace('_', ' ', strtolower($cmd)));
            $method = "process" . str_replace(' ', '', $method) . "Directive";

            if (!is_callable(array($this, $method))) {
                throw new \RuntimeException("Directive $cmd is not defined");
            }

            call_user_func_array(array($this, $method), $args);
        }
    }

    protected function parseDirectives($header)
    {
        $directives = array();
        $index = 0;

        foreach (explode("\n", $header) as $line) {
            if (!preg_match(self::DIRECTIVE_PATTERN, $line, $matches)) {
                continue;
            }
            $directives[] = array_merge(array(++$index), explode(' ', $matches[1]));
        }
        return $directives;
    }

    protected function isRelative($path)
    {
        if (preg_match('/^\.(\/|\\\\).+/', $path)) {
            return true;
        }
        return false;
    }
}
