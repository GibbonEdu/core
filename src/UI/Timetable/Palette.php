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

namespace Gibbon\UI\Timetable;

/**
 * Timetable UI Colour Palette
 *
 * @version  v30
 * @since    v30
 */
class Palette
{
    protected $colors = [
        'gray' => [
            'background'   => 'bg-gray-200/90',
            'text'         => 'text-gray-700',
            'textLight'    => 'text-gray-400',
            'textHover'    => 'hover:text-gray-800',
            'outline'      => 'outline-gray-500',
            'outlineLight' => 'outline-gray-500/50',
            'outlineHover' => 'hover:outline-gray-600',
        ],
        'blue' => [
            'background'   => 'bg-blue-200',
            'text'         => 'text-blue-900',
            'textLight'    => 'text-blue-400',
            'textHover'    => 'hover:text-blue-950',
            'outline'      => 'outline-blue-700',
            'outlineLight' => 'outline-blue-700/50',
            'outlineHover' => 'hover:outline-blue-600',
        ],
        'cyan' => [
            'background'   => 'bg-cyan-200',
            'text'         => 'text-cyan-800',
            'textLight'    => 'text-cyan-400',
            'textHover'    => 'hover:text-cyan-950',
            'outline'      => 'outline-cyan-700',
            'outlineLight' => 'outline-cyan-700/50',
            'outlineHover' => 'hover:outline-cyan-600',
        ],
        'pink' => [
            'background'   => 'bg-pink-300',
            'textLight'    => 'text-pink-400',
            'text'         => 'text-pink-800',
            'textHover'    => 'hover:text-pink-950',
            'outline'      => 'outline-pink-800',
            'outlineLight' => 'outline-pink-800/50',
            'outlineHover' => 'hover:outline-pink-600',
        ],
        'green' => [
            'background'   => 'bg-green-200',
            'textLight'    => 'text-green-400',
            'text'         => 'text-green-800',
            'textHover'    => 'hover:text-green-950',
            'outline'      => 'outline-green-700',
            'outlineLight' => 'outline-green-700/50',
            'outlineHover' => 'hover:outline-green-600',
        ],
        'teal' => [
            'background'   => 'bg-teal-200',
            'text'         => 'text-teal-800',
            'textLight'    => 'bg-teal-400',
            'textHover'    => 'hover:text-teal-950',
            'outline'      => 'outline-teal-700',
            'outlineLight' => 'outline-teal-700/50',
            'outlineHover' => 'hover:outline-teal-600',
        ],
        'yellow' => [
            'background'   => 'bg-yellow-200',
            'text'         => 'text-yellow-800',
            'textLight'    => 'text-yellow-400',
            'textHover'    => 'hover:text-yellow-950',
            'outline'      => 'outline-yellow-700',
            'outlineLight' => 'outline-yellow-700/50',
            'outlineHover' => 'hover:outline-yellow-600',
        ],
        'orange' => [
            'background'   => 'bg-orange-200',
            'text'         => 'text-orange-800',
            'textLight'    => 'text-orange-400',
            'textHover'    => 'hover:text-orange-900',
            'outline'      => 'outline-orange-700',
            'outlineLight' => 'outline-orange-700/50',
            'outlineHover' => 'hover:outline-orange-600',
        ],
        'purple' => [
            'background'   => 'bg-purple-200',
            'text'         => 'text-purple-800',
            'textLight'    => 'text-purple-400',
            'textHover'    => 'hover:text-purple-950',
            'outline'      => 'outline-purple-700',
            'outlineLight' => 'outline-purple-700/50',
            'outlineHover' => 'hover:outline-purple-600',
        ],
        'red' => [
            'background'   => 'bg-red-200',
            'text'         => 'text-red-800',
            'textLight'    => 'text-red-400',
            'textHover'    => 'hover:text-red-950',
            'outline'      => 'outline-red-700',
            'outlineLight' => 'outline-red-700/50',
            'outlineHover' => 'hover:outline-red-600',
        ],
    ];

    public function getPalette($color = null)
    {
        if (substr($color, 0, 1) == '#') {
            $border = $this->adjustHexColor($color, -0.15);
            $text = $this->adjustHexColor($color, -0.7);
            return [
                'style'     => "background-color: {$color}; outline-color: {$border}; color: {$text};",
                'bgStyle'   => "background-color: {$color};",
                'textStyle' => "color: {$text};",
            ];
        }
        return $this->colors[$color] ?? $this->colors['gray'];
    }

    public function adjustHexColor($hexCode, $adjustPercent) {
        $hexCode = ltrim($hexCode, '#');
    
        if (strlen($hexCode) == 3) {
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        }
    
        $hexCode = array_map('hexdec', str_split($hexCode, 2));
    
        foreach ($hexCode as & $color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);
    
            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }
    
        return '#' . implode($hexCode);
    }

    public function getHexContrastColor($hexcolor) {
        // Remove '#' if present
        $hexcolor = str_replace('#', '', $hexcolor);
    
        // Get RGB components
        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));
    
        // Calculate YIQ value (perceived brightness)
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    
        // Return black or white based on YIQ threshold
        return ($yiq >= 128) ? 'black' : 'white';
    }
}
