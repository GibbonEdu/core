<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\View;

/**
 * A collection of HTML assets (scripts & stylesheets) which can be registered for later use.
 *
 * @version v17
 * @since   v17
 */
class AssetBundle
{
    protected $allAssets = [];
    protected $usedAssetsByName = [];

    /**
     * Register a named asset for later use. Name should be unique.
     *
     * @param string $name      Unique identifier for this asset.
     * @param string $src       URL, relative to the system absolutePath, or 
     *                          inline content depending on `type` in `$options`
     * @param array  $options   Options for rendering, includes these fields:
     *                          - string `type`
     *                                  'url' (default) for URL as $content.
     *                                  'inline' for inline script or style as $content.
     *                          - string `context`
     *                                  The output location, eg: 'head', 'foot'
     *                          - string 'media'
     *                                  The media type (stylesheets only), eg: 'all', 'screen', 'print'
     *                          - mixed `version`
     *                                  The version number is appended to the asset URL for cache-busting.
     *                          - mixed `weight`
     *                                  Determines the execution order of assets.
     */
    public function register($name, $src, array $options = [])
    {
        $this->allAssets[$name] = array_replace([
            'src'     => $src,
            'type'    => 'url',
            'context' => 'head',
            'media'   => 'all',
            'version' => null,
            'weight'  => null,
        ], $options);
    }

    /**
     * Add an asset, optionally only providing the name of one previously registered.
     *
     * @param string $name      Unique identifier for this asset.
     * @param mixed  $src
     * @param array  $options
     * 
     * @see register
     */
    public function add($name, $src = null, $options = [])
    {
        if (!is_null($src)) {
            $this->register($name, $src, $options);
        }

        $this->usedAssetsByName[] = $name;
    }

    /**
     * Get all assets. Optionally filter returned assets by context.
     *
     * @param string $context
     * @return array
     */
    public function getAssets($context = null)
    {
        $usedAssets = array_intersect_key($this->allAssets, array_flip($this->usedAssetsByName));

        return array_filter($usedAssets, function($item) use ($context) {
            return empty($context) || $item['context'] == $context;
        });
    }
}
