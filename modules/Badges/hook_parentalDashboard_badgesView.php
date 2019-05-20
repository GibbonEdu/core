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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$returnInt = null;

//Only include module include if it is not already included (which it may be been on the index page)
$included = false;
$includes = get_included_files();
foreach ($includes as $include) {
    if ($include == $gibbon->session->get('absolutePath','').'/modules/Badges/moduleFunctions.php') {
        $included = true;
    }
}
if ($included == false) {
    include './modules/Badges/moduleFunctions.php';
}

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_view.php') == false) {
    //Acess denied
    $returnInt .= "<div class='error'>";
    $returnInt .= 'You do not have access to this action.';
    $returnInt .= '</div>';
} else {
    $returnInt .= getBadges($connection2, $guid, $gibbonPersonID);
}

return $returnInt;
