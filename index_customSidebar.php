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

$output = '';

$roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDPrimary'], $connection2);

if ($roleCategory == 'Staff') {
    $output .= '<h2>' . __('Staff Portal') . '</h2>';
    $output .= '<p>'.__('Please visit the portal each morning:').'<br/>';
    $output .= '<input type="button" 
                       class="fullWidth" value="' . __("Today's Announcements ⇒") . '" 
                       onClick="window.open(\''.$_SESSION[$guid]['webLink'].'/portal/\')" 
                       style="height:32px;margin-bottom:15px; background:#C4ECFF; cursor: pointer;">';
    $output .= '</p>';
}

return $output;