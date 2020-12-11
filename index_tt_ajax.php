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

//Gibbon system-wide includes
include './gibbon.php';

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code']) && function_exists('gettext')) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$gibbon->session->get('i18n')['code']);
        setlocale(LC_ALL, $gibbon->session->get('i18n')['code']);
        bindtextdomain('gibbon', './i18n');
        textdomain('gibbon');
        bind_textdomain_codeset('gibbon', 'UTF-8');
    }
}

//Setup variables
$output = '';
$id = $_POST['gibbonTTID'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __('Your request failed because you do not have access to this action.');
    $output .= '</div>';
} else {
    include './modules/Timetable/moduleFunctions.php';
    $ttDate = '';
    if (!empty($_POST['ttDate'])) {
        $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
    }

    $tt = renderTT($guid, $connection2, $gibbon->session->get('gibbonPersonID'), $id, false, $ttDate, '', '', 'trim');
    if ($tt != false) {
        $output .= $tt;
    } else {
        $output .= "<div class='error'>";
        $output .= __('There is no information for the date specified.');
        $output .= '</div>';
    }
}

echo $output;
