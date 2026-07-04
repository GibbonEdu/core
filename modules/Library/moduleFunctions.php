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

function getImage($guid, $type, $location, $border = true)
{
    $output = false;

    $borderStyle = '';
    if ($border == true) {
        $borderStyle = '; border: 1px dashed #666';
    }

    if ($location == '') {
        $output .= "<img style='height: 240px; width: 240px; opacity: 1.0' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_240_square.jpg'/><br/>";
    } else {
        if ($type == 'Link') {
            $output .= "<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center $borderStyle'>";
            $output .= "<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' src='".$location."'/><br/>";
            $output .= '</div>';
        }
        if ($type == 'File') {
            if (is_file($_SESSION[$guid]['absolutePath'].'/'.$location)) {
                $output .= "<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center; $borderStyle'>";
                $output .= "<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' title='' src='".$_SESSION[$guid]['absoluteURL'].'/'.$location."'/><br/>";
                $output .= '</div>';
            } else {
                $output .= "<img style='height: 240px; width: 240px; opacity: 1.0' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_240_square.jpg'/><br/>";
            }
        }
    }

    return $output;
}
