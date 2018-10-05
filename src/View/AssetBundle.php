<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version >= 7.0
 *
 * @category File
 * @package  Gibbon
 * @author   Ross Parker <ross@rossparker.org>
 * @license  GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link     https://gibbonedu.org/
 */

namespace Gibbon\View;

/**
 * A collection of HTML assets (scripts & stylesheets) which can be registered
 * for later use.
 *
 * @category Class
 * @package  Gibbon\View
 * @author   Ross Parker <ross@rossparker.org>
 * @license  GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @version  Release: v17
 * @link     https://gibbonedu.org/
 * @since    Release: v17 
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
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function register(string $name, string $src, array $options = []): void
    {
        $this->allAssets[$name] = array_replace(
            [
                'src' => $src,
                'type' => 'url',
                'context' => 'head',
                'media' => 'all',
                'version' => null,
                'weight' => null,
            ], $options
        );
    }

    /**
     * Add an asset, optionally only providing the name of one previously registered.
     *
     * @param string $name    Unique identifier for this asset.
     * @param mixed  $src     Source string of the asset.
     * @param array  $options Additional options for the registered asset.
     *
     * @see register
     *
     * @return void
     */
    public function add(string $name, $src = null, array $options = []): void
    {
        if (!is_null($src)) {
            $this->register($name, $src, $options);
        }

        $this->usedAssetsByName[] = $name;
    }

    /**
     * Get all assets sorted by weight, ascending. Optionally filter returned
     * assets by context.
     *
     * @param string $context Get assets of certain context.
     *
     * @return array
     */
    public function getAssets(string $context = null): array
    {
        $usedAssets = array_intersect_key(
            $this->allAssets, array_flip($this->usedAssetsByName)
        );

        uasort(
            $usedAssets, function ($a, $b) {
                return $a['weight'] <=> $b['weight'];
            }
        );

        return array_filter(
            $usedAssets, function ($item) use ($context) {
                return empty($context) || $item['context'] == $context;
            }
        );
    }
}
