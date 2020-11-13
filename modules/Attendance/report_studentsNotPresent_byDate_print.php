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

use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotPresent_byDate_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    if ($_GET['currentDate'] == '') {
        $currentDate = date('Y-m-d');
    } else {
        $currentDate = dateConvert($guid, $_GET['currentDate']);
    }

    $allStudents = !empty($_GET["allStudents"])? 1 : 0;
    $sort = !empty($_GET['sort'])? $_GET['sort'] : 'surname, preferredName';
    $gibbonYearGroupIDList = (!empty($_GET['gibbonYearGroupIDList'])) ? explode(',', $_GET['gibbonYearGroupIDList']) : null ;

    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo);

    //Proceed!
    echo '<h2>';
    echo __('Students Not Present').', '.dateConvertBack($guid, $currentDate);
    echo '</h2>';

    //Produce array of attendance data
    try {
        $countClassAsSchool = getSettingByScope($connection2, 'Attendance', 'countClassAsSchool');
        $data = array('date' => $currentDate);
        $sql = 'SELECT *
            FROM gibbonAttendanceLogPerson
            WHERE date=:date';
            if ($countClassAsSchool == "N") {
                $sql .= ' AND NOT context=\'Class\'';
            }
            $sql .= ' ORDER BY gibbonPersonID, gibbonAttendanceLogPersonID DESC';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $log = array();
    $currentStudent = '';
    $lastStudent = '';
    while ($row = $result->fetch()) {
        $currentStudent = $row['gibbonPersonID'];
        if ( $attendance->isTypePresent($row['type']) and $currentStudent != $lastStudent) {
            $log[$row['gibbonPersonID']] = true;
        }
        $lastStudent = $currentStudent;
    }

    try {
        $orderBy = 'ORDER BY surname, preferredName, LENGTH(rollGroup), rollGroup';
        if ($sort == 'preferredName')
            $orderBy = 'ORDER BY preferredName, surname, LENGTH(rollGroup), rollGroup';
        if ($sort == 'rollGroup')
            $orderBy = 'ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName';

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

        $whereExtra = '';
        if (is_array($gibbonYearGroupIDList)) {
            $data['gibbonYearGroupIDList'] = implode(",", $gibbonYearGroupIDList);
            $whereExtra = ' AND FIND_IN_SET (gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)';
        }

        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as rollGroupName, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $whereExtra";

        $sql .= $orderBy;

        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo "<div class='linkTop'>";
        echo "<a href='javascript:window.print()'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';

        $lastPerson = '';

        echo "<table class='mini' cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Count');
        echo '</th>';
        echo '<th style="width:80px">';
        echo __('Roll Group');
        echo '</th>';
        echo '<th>';
        echo __('Name');
        echo '</th>';
        echo '<th>';
        echo __('Status');
        echo '</th>';
        echo '<th>';
        echo __('Reason');
        echo '</th>';
        echo '<th>';
        echo __('Comment');
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

                try {
                    $dataAttendance = array('date' => $currentDate, 'gibbonPersonID' => $row['gibbonPersonID']);
                    $sqlAttendance = 'SELECT *
                        FROM gibbonAttendanceLogPerson
                        WHERE date=:date
                        AND gibbonPersonID=:gibbonPersonID';
                        if ($countClassAsSchool == "N") {
                            $sqlAttendance .= ' AND NOT context=\'Class\'';
                        }
                        $sqlAttendance .= ' ORDER BY gibbonAttendanceLogPersonID DESC';
                    $resultAttendance = $connection2->prepare($sqlAttendance);
                    $resultAttendance->execute($dataAttendance);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                // Skip rows with no record if we're not displaying all students
                if ($resultAttendance->rowCount()<1 && $allStudents == FALSE) {
                    continue;
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                    echo $count;
                echo '</td>';
                echo '<td>';
                    echo $row['rollGroupName'];
                echo '</td>';
                echo '<td>';
                    echo Format::name('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                echo '</td>';
                echo '<td>';
                $rowRollAttendance = null;

                if ($resultAttendance->rowCount() < 1) {
                    echo Format::small(__('Not registered'));
                } else {
                    $rowRollAttendance = $resultAttendance->fetch();
                    echo __($rowRollAttendance['type']);
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
            echo __('All students are present.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
