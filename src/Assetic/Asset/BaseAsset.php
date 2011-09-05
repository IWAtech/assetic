<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Asset;

use Assetic\Filter\FilterCollection;
use Assetic\Filter\FilterInterface;

/**
 * A base abstract asset.
 *
 * The methods load() and getLastModified() are left undefined, although a
 * reusable doLoad() method is available to child classes.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class BaseAsset implements AssetInterface
{
    private $filters;
    private $sourceRoot;
    private $sourcePath;
    private $targetPath;
    private $content;
    private $loaded;

    /**
     * This dependencies are only checked on their
     * Last Modified Time and get not concatenated with
     * the Asset's content.
     *
     * Use this if you have no control over file including
     * in an external Parser (e.g. LESS,...)
     *
     * @var AssetCollection
     */
    protected $dependencies;

    /**
     * Dependencies which get dumped before the asset's content
     * @var AssetCollection
     */
    protected $required;

    /**
     * Constructor.
     *
     * @param array $filters Filters for the asset
     */
    public function __construct($filters = array(), $sourceRoot = null, $sourcePath = null)
    {
        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->sourcePath = $sourcePath;
        $this->loaded = false;

        $this->dependencies = new AssetCollection;
        $this->required = new AssetCollection;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
        $this->dependencies = new AssetCollection;
        $this->required = new AssetCollection;
    }

    public function addDependency(AssetInterface $asset)
    {
        $this->dependencies->add($asset);
    }

    public function addRequiredDependency(AssetInterface $asset)
    {
        $this->required->add($asset);
    }

    /**
     * Returns all Dependencies as Asset Collection
     *
     * @return AssetCollection
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    public function getRequiredDependencies()
    {
        return $this->required;
    }

    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    public function getFilters()
    {
        return $this->filters->all();
    }

    public function clearFilters()
    {
        $this->filters->clear();
    }

    /**
     * Encapsulates asset loading logic.
     *
     * @param string          $content          The asset content
     * @param FilterInterface $additionalFilter An additional filter
     */
    protected function doLoad($content, FilterInterface $additionalFilter = null)
    {
        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $asset->setContent($content);

        $filter->filterLoad($asset);

        foreach ($asset->getDependencies() as $dep) {
            $this->addDependency($dep);
        }

        foreach ($asset->getRequiredDependencies() as $dep) {
            $this->addRequiredDependency($dep);
        }

        $this->content = $asset->getContent();
        $this->loaded = true;
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->loaded) {
            $this->load();
        }

        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $this->required->load();

        $asset = clone $this;
        $asset->setContent($this->required->dump().$this->getContent());
        $filter->filterDump($asset);

        return $asset->getContent();
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getSourceRoot()
    {
        return $this->sourceRoot;
    }

    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    public function getTargetPath()
    {
        return $this->targetPath;
    }

    public function setTargetPath($targetPath)
    {
        $this->targetPath = $targetPath;
    }
}
