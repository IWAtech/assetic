<?php

namespace Assetic\Filter\DirectiveProcessor;

use Assetic\Asset\AssetInterface,
    Assetic\Filter\DirectiveProcessor;

interface Directive
{
    /**
     * The Directive's name
     */
    function getName();

    function setProcessor(DirectiveProcessor $processor);

    /**
     * Called whenever this directive is found within the source
     *
     * @param  AssetInterface $parent The file where the directive was found
     * @param  array $argv
     * @return AssetInterface|null
     */
    function execute(AssetInterface $parent, array $argv);
}
