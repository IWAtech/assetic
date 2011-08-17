<?php

namespace Assetic\Filter\DirectiveProcessor;

use Assetic\Filter\DirectiveProcessor,
    Assetic\Asset\AssetInterface,
    Assetic\Asset\FileAsset;

class RequireDirective implements Directive
{
    protected $processor;

    function getName()
    {
        return "require";
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

        $requiredFile = $cwd . '/' . $requiredFile;

		if (is_callable(array($parent, 'getDependencies'))) {
			foreach ($parent->getDependencies() as $asset) {
				if ($asset->getSourceRoot().'/'.$asset->getSourcePath() == $requiredFile) {
					return;
				}
			}
		}
		
		$parent->addDependency(new FileAsset($requiredFile, array($this->processor)));
    }
}
