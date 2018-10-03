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
    protected $registeredAssets = [];
    protected $usedAssets = [];

    /**
     * Register a named asset for later use. Name should be unique.
     *
     * @param string $name      Unique identifier for this asset.
     * @param string $src       URL, relative to the system absolutePath.
     * @param string $context   Scripts: context should be the output location, eg: 'head', 'foot'
     *                          Styles: context should be the media type, eg: 'all', 'screen', 'print'
     * @param string $version   The version number is appended to the asset URL for cache-busting.
     */
    public function register($name, $src, $context, $version = null)
    {
        $this->registeredAssets[$name] = [
            'src'     => $src,
            'context' => $context,
            'version' => $version,
        ];
    }

    /**
     * Add an asset, optionally only providing the name of one previously registered.
     *
     * @param string $name      Unique identifier for this asset.
     * @param mixed $src
     * @param mixed $context
     * @param mixed $version
     * 
     * @see register
     */
    public function add($name, $src = null, $context = null, $version = null)
    {
        if (is_null($src) && isset($this->registeredAssets[$name])) {
            $asset = $this->registeredAssets[$name];
            return $this->add($name, $asset['src'], $asset['context'], $asset['version']);
        }

        $this->usedAssets[$name] = [
            'src'     => $src,
            'context' => $context,
            'version' => $version,
        ];
    }

    /**
     * Get all assets. Optionally filter returned assets by context.
     *
     * @param string $context
     * @return array
     */
    public function getAssets($context = null)
    {
        return array_filter($this->usedAssets, function($item) use ($context) {
            return empty($context) || $item['context'] == $context;
        });
    }
}
