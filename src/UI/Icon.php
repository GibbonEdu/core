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

namespace Gibbon\UI;

use Gibbon\View\Component;

/**
 * Easy access to SVG icons.
 *
 * @version  v28
 * @since    v28
 */
class Icon
{
    protected static $libraries = [
        'Basic',
        'Solid',
        'Outline',
        'Large',
    ];

    /**
     * Return an SVG icon from a specified icon library.
     * Many of the icons come from: https://heroicons.com
     *
     * @param string $library   One of: basic, solid, outline
     * @param string $icon      The name of an icon
     * @param string $class     Applies a class to the svg returned
     * @param array $options    Eg: strokeWidth for outline icons
     * @return string
     */
    public static function get(string $library, string $icon, string $class = '', array $options = []) : string
    {
        if (!$path = static::getLibraryPath($library)) {
            return '';
        }

        return Component::render($path, [
            'icon'    => strtolower($icon),
            'class'   => $class , //.' pointer-events-none'
            'options' => $options,
        ]);
    }

    /**
     * Validate and get the path to a set of icons.
     *
     * @param string $library
     * @return string
     */
    protected static function getLibraryPath(string $library) : string
    {
        $library = ucfirst(strtolower($library));

        if (!in_array($library, static::$libraries)) {
            return false;
        }

        return 'UI/Icons/Icons'.$library;
    }
}
