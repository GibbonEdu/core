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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_my.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('My Activities')); 

    
        $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'), 'gibbonPersonID2' => $gibbon->session->get('gibbonPersonID'), 'gibbonSchoolYearID2' => $gibbon->session->get('gibbonSchoolYearID'));
        $sql = "(SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y') UNION (SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivityStaff.gibbonPersonID=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID2 AND active='Y') ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Activity');
        echo '</th>';
        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
        if ($options != '') {
            echo '<th>';
            echo __('Type');
            echo '</th>';
        }
        echo '<th>';
        echo __('Role');
        echo '</th>';
        echo '<th>';
        echo __('Status');
        echo '</th>';
        echo '<th>';
        echo __('Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            if ($options != '') {
                echo '<td>';
                echo trim($row['type']);
                echo '</td>';
            }
            echo '<td>';
            if ($row['role'] == '') {
                echo __('Student');
            } else {
                echo __($row['role']);
            }
            echo '</td>';
            echo '<td>';
            if ($row['status'] != '') {
                echo __($row['status']);
            } else {
                echo '<i>'.__('NA').'</i>';
            }
            echo '</td>';
            echo '<td>';
            if ($row['role'] == 'Organiser' && isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php')) {
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/activities_manage_enrolment.php&gibbonActivityID='.$row['gibbonActivityID']."&search=&gibbonSchoolYearTermID='><img title='".__('Enrolment')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
            }

            echo "<a class='thickbox' href='".$gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$gibbon->session->get('module').'/activities_my_full.php&gibbonActivityID='.$row['gibbonActivityID']."&width=1000&height=550'><img title='".__('View Details')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/plus.png'/></a> ";

            if ($highestAction == "Enter Activity Attendance" || ($highestAction == "Enter Activity Attendance_leader" && ($row['role'] == 'Organiser' || $row['role'] == 'Assistant' || $row['role'] == 'Coach'))) {
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/activities_attendance.php&gibbonActivityID='.$row['gibbonActivityID']."'><img title='".__('Attendance')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/attendance.png'/></a> ";
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
