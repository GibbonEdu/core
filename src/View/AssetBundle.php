<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
 * A collection of HTML assets (scripts & stylesheets) which can be registered
 * for later use.
 *
 * @version  v17
 * @since    v17
 */
class AssetBundle
{
    protected $allAssets = [];
    protected $usedAssetsByName = [];

    /**
     * Register a named asset for later use. Name should be unique.
     *
     * @param string $name    Unique identifier for this asset.
     * @param string $src     URL, relative to the system absolutePath, or
     *                        inline content depending on `type` in `$options`
     * @param array  $options Options for rendering, includes these fields:
     *
     *                        string ['type']
     *                        'url' (default) for URL as $content.
     *                        'inline' for inline script or style as $content.
     *
     *                        string ['context']
     *                        The output location, eg: 'head', 'foot'
     *
     *                        string ['media']
     *                        The media type (stylesheets only),
     *                        eg: 'all', 'screen', 'print'.
     *
     *                        mixed ['version']
     *                        The version number is appended to the asset URL
     *                        for cache-busting.
     *
     *                        int|null ['weight']
     *                        Determines the execution order of assets.
     */
    public function register(string $name, string $src, array $options = [])
    {
        $this->allAssets[$name] = array_replace([
            'src'     => trim($src, '/ '),
            'type'    => 'url',
            'context' => 'foot',
            'media'   => 'all',
            'version' => null,
            'weight'  => 0,
            'index'   => count($this->allAssets),
        ], $options);
    }

    /**
     * Add an asset, optionally only providing the name of one previously registered.
     *
     * @param string $name     Unique identifier for this asset.
     * @param mixed  $src      Source string of the asset.
     * @param array  $options  Additional options for the registered asset.
     *
     * @see register
     */
    public function add(string $name, $src = null, array $options = [])
    {
        if (!is_null($src)) {
            $this->register($name, $src, $options);
        }

        $this->usedAssetsByName[$name] = $name;
    }

    public function remove(string $name)
    {
        if (isset($this->usedAssetsByName[$name])) {
            unset($this->usedAssetsByName[$name]);
        }
    }

    /**
     * Add an array of assets that each share the same options.
     *
     * @param array $assets
     * @param array $options
     */
    public function addMultiple(array $assets, array $options = [])
    {
        foreach ($assets as $name => $src) {
            if (is_array($src)) {
                $this->add($name, $src['src'], $src);
            } else {
                $this->add($name, $src, $options);
            }
        }
    }

    /**
     * Get all assets sorted by weight, ascending. The index value is used to
     * maintain the original array order when weights are equal.
     *
     * @param string $context Optionally filter returned assets by context.
     * @return array
     */
    public function getAssets(string $context = null): array
    {
        $usedAssets = array_intersect_key($this->allAssets, $this->usedAssetsByName);

        uasort($usedAssets, function ($a, $b) {
            if ($a['weight'] != $b['weight']) {
                return $a['weight'] <=> $b['weight'];
            }
            
            return $a['index'] <=> $b['index'];
        });

        return array_filter($usedAssets, function ($item) use ($context) {
            return empty($context) || $item['context'] == $context;
        });
    }
}
