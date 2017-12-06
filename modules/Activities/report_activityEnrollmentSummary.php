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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityEnrollmentSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Activity Enrolment Summary').'</div>';
    echo '</div>';
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $lastPerson = '';

        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Activity');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Accepted');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Registered').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Excludes "Not Accepted"').'<span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Max Participants');
        echo '</th>';
        echo '</tr>';

        while ($row = $result->fetch()) {
            try {
                $dataEnrollment = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $row['gibbonActivityID']);
                $sqlEnrollment = "SELECT gibbonActivityStudent.* FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date('Y-m-d') . "') AND (dateEnd IS NULL  OR dateEnd>='" . date('Y-m-d') . "') AND gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.status='Accepted'";
                $resultEnrollment = $connection2->prepare($sqlEnrollment);
                $resultEnrollment->execute($dataEnrollment);
            } catch (PDOException $e) {
                echo "<div class='error'>" . $e->getMessage() . '</div>';
            }
            $enrolmentCount = $resultEnrollment->rowCount();

            $rowClass = '';
            if ($enrolmentCount == $row['maxParticipants'] && $row['maxParticipants'] > 0) {
                $rowClass = 'current';
            } else if ($enrolmentCount > $row['maxParticipants']) {
                $rowClass = 'error';
            } else if ($row['maxParticipants'] == 0) {
                $rowClass = 'warning';
            }

            echo '<tr class="'.$rowClass.'">';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';

            if ($enrolmentCount < 0) {
                echo '<i>'.__($guid, 'Unknown').'</i>';
            } else {
                if ($enrolmentCount > $row['maxParticipants']) {
                    echo "<span style='color: #f00; font-weight: bold'>".$enrolmentCount.'</span>';
                } else {
                    echo $enrolmentCount;
                }
            }
            echo '</td>';
            echo '<td>';
            try {
                $dataEnrollment = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $row['gibbonActivityID']);
                $sqlEnrollment = "SELECT gibbonActivityStudent.* FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityID=:gibbonActivityID AND NOT gibbonActivityStudent.status='Not Accepted'";
                $resultEnrollment = $connection2->prepare($sqlEnrollment);
                $resultEnrollment->execute($dataEnrollment);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($enrolmentCount < 0) {
                echo '<i>'.__($guid, 'Unknown').'</i>';
            } else {
                echo $enrolmentCount;
            }
            echo '</td>';
            echo '<td>';
            echo $row['maxParticipants'];
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
