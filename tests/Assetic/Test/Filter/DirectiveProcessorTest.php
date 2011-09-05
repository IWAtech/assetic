<?php
/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter;

use Assetic\Filter\DirectiveProcessor,
    Assetic\Asset\FileAsset;

/**
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class DirectiveProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DirectiveProcessor
     */
    protected $processor;
    protected $asset;

    function setUp()
    {
        $this->processor = new DirectiveProcessor;
        $this->asset = new FileAsset(
            __DIR__ . DIRECTORY_SEPARATOR . "fixtures" . DIRECTORY_SEPARATOR . "directiveprocessor" . DIRECTORY_SEPARATOR . "test1.js"
        );

        $this->asset->ensureFilter($this->processor);
    }

    function testRequire()
    {
        $content = <<<JAVASCRIPT
// file bar.js
var foo = "bar";

// file foo.js
var bar = "baz";
// The manifest file
/**
* Some multiline comment which isn't modified
*/
/*
*/
var foo = function() {
}();

JAVASCRIPT;

        $this->assertEquals($content, $this->asset->dump());
    }

    function testDependOn()
    {
        $mtime = time();

        $this->asset->load();

        touch(__DIR__.'/fixtures/directiveprocessor/test/dependency.js', $mtime);

        $this->assertEquals($mtime, $this->asset->getLastModified());
    }
}
