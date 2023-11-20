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

function getImage($guid, $type, $location, $border = true)
{
    global $session;

    $output = false;

    $borderStyle = '';
    if ($border == true) {
        $borderStyle = '; border: 1px dashed #666';
    }

    if ($location == '') {
        $output .= "<img style='height: 240px; width: 240px; opacity: 1.0' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
    } else {
        if ($type == 'Link') {
            $output .= "<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center $borderStyle'>";
            $output .= "<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' src='".$location."'/><br/>";
            $output .= '</div>';
        }
        if ($type == 'File') {
            if (is_file($session->get('absolutePath').'/'.$location)) {
                $output .= "<div style='height: 240px; width: 240px; display:table-cell; vertical-align:middle; text-align:center; $borderStyle'>";
                $output .= "<img class='user' style='max-height: 240px; max-width: 240px; opacity: 1.0; margin: auto' title='' src='".$session->get('absoluteURL').'/'.$location."'/><br/>";
                $output .= '</div>';
            } else {
                $output .= "<img style='height: 240px; width: 240px; opacity: 1.0' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
            }
        }
    }

    return $output;
}
