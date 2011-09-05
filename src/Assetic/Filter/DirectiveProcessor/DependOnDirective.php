<?php

namespace Assetic\Filter\DirectiveProcessor;

use Assetic\Filter\DirectiveProcessor,
    Assetic\Asset\AssetInterface,
    Assetic\Asset\FileAsset;

class DependOnDirective implements Directive
{
    protected $processor;

    function getName()
    {
        return "depend_on";
    }

    function setProcessor(DirectiveProcessor $processor)
    {
        $this->processor = $processor;
    }

    function execute(AssetInterface $parent, array $argv)
    {
        $cwd = $parent->getSourceRoot();
        $requiredFile = array_shift($argv);

        // Use the parent's extension if no extension is given
        if ('' === pathinfo($requiredFile, PATHINFO_EXTENSION)) {
            $requiredFile .= '.' . pathinfo($parent->getSourcePath(), PATHINFO_EXTENSION);
        }

        $requiredFile = "$cwd/$requiredFile";
        $parent->addDependency(
            new FileAsset($requiredFile, array($this->processor))
        );
    }
}
