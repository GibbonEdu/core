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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotOnsite_byDate_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    if ($_GET['currentDate'] == '') {
        $currentDate = date('Y-m-d');
    } else {
        $currentDate = dateConvert($guid, $_GET['currentDate']);
    }

    //Proceed!
    echo '<h2>';
    echo __($guid, 'Students Not Onsite').', '.dateConvertBack($guid, $currentDate);
    echo '</h2>';

    //Produce array of attendance data
    try {
        $data = array('date' => $currentDate);
        $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date ORDER BY gibbonPersonID, gibbonAttendanceLogPersonID DESC';
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
        $log = array();
        $currentStudent = '';
        $lastStudent = '';
        while ($row = $result->fetch()) {
            $currentStudent = $row['gibbonPersonID'];
            if (($row['type'] == 'Present' or $row['type'] == 'Present - Late') and $currentStudent != $lastStudent) {
                $log[$row['gibbonPersonID']] = true;
            }
            $lastStudent = $currentStudent;
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
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
            echo "<div class='linkTop'>";
            echo "<a href='javascript:window.print()'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            $lastPerson = '';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Status');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Reason');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Comment');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if (isset($log[$row['gibbonPersonID']]) == false) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    try {
                        $dataRollGroup = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                        $sqlRollGroup = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                        $resultRollGroup = $connection2->prepare($sqlRollGroup);
                        $resultRollGroup->execute($dataRollGroup);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultRollGroup->rowCount() < 1) {
                        echo '<i>'.__($guid, 'Unknown').'</i>';
                    } else {
                        $rowRollGroup = $resultRollGroup->fetch();
                        echo $rowRollGroup['name'];
                    }

                    echo '</td>';
                    echo '<td>';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                    echo '</td>';
                    echo '<td>';
                    $rowRollAttendance = null;
                    try {
                        $dataAttendance = array('date' => $currentDate, 'gibbonPersonID' => $row['gibbonPersonID']);
                        $sqlAttendance = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC';
                        $resultAttendance = $connection2->prepare($sqlAttendance);
                        $resultAttendance->execute($dataAttendance);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultAttendance->rowCount() < 1) {
                        echo '<i>Not registered</i>';
                    } else {
                        $rowRollAttendance = $resultAttendance->fetch();
                        echo $rowRollAttendance['type'];
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $rowRollAttendance['reason'];
                    echo '</td>';
                    echo '<td>';
                    echo $rowRollAttendance['comment'];
                    echo '</td>';
                    echo '</tr>';

                    $lastPerson = $row['gibbonPersonID'];
                }
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=5>';
                echo __($guid, 'All students are present.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
