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

use Gibbon\Forms\Form;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendance_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance by Activity').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $today = date('Y-m-d');
    $date = (isset($_POST['date']))? dateConvert($guid, $_POST['date']) : date('Y-m-d');
    $sort = (isset($_POST['sort']))? $_POST['sort'] : 'surname';

    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    // Options & Filters
    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_attendance_byDate.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('date')->setValue(dateConvertBack($guid, $date))->isRequired();

    $sortOptions = array('absent' => __('Absent'), 'surname' => __('Surname'), 'preferredName' => __('Given Name'), 'rollGroup' => __('Roll Group'));
    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    // Cancel out early if we have no date
    if (empty($date)) {
        return;
    }

    if ($date > $today) {
        print "<div class='error'>" ;
            print __('The specified date is in the future: it must be today or earlier.');
        print "</div>" ;
    } else if (isSchoolOpen($guid, $date, $connection2)==FALSE) {
        print "<div class='error'>" ;
            print __('School is closed on the specified date, and so attendance information cannot be recorded.') ;
        print "</div>" ;
    } else {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        //Turn $date into UNIX timestamp and extract day of week
        $dayOfWeek = date('l', dateConvertToTimestamp($date));

        $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

        // Handle activiies by Date and Term
        if ($dateType == 'Date') {
            $data = array('date' => $date, 'dayOfWeek' => $dayOfWeek, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT DISTINCT gibbonActivity.gibbonActivityID
                    FROM gibbonActivity
                    JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
                    JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
                    WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND (:date BETWEEN gibbonActivity.programStart AND gibbonActivity.programEnd)
                    AND gibbonDaysOfWeek.name=:dayOfWeek
                    AND gibbonActivity.active='Y'
                    GROUP BY gibbonActivity.gibbonActivityID";
        } else {
            $data = array('date' => $date, 'dayOfWeek' => $dayOfWeek, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT DISTINCT gibbonActivity.gibbonActivityID
                    FROM gibbonActivity
                    JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID)
                    JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
                    JOIN gibbonSchoolYearTerm ON (gibbonActivity.gibbonSchoolYearTermIDList LIKE CONCAT('%', gibbonSchoolYearTermID, '%'))
                    WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND (:date BETWEEN gibbonSchoolYearTerm.firstDay AND gibbonSchoolYearTerm.lastDay)
                    AND gibbonDaysOfWeek.name=:dayOfWeek
                    AND gibbonActivity.active='Y'
                    GROUP BY gibbonActivity.gibbonActivityID";
        }

        try {
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if (!$result || $result->rowCount() == 0) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            $gibbonActivityIDList = $result->fetchAll(\PDO::FETCH_COLUMN, 0);

            try {
                // Order By switch
                switch ($sort) {
                    case 'surname':         $orderBy = 'ORDER BY surname, preferredName'; break;
                    case 'preferredName':   $orderBy = 'ORDER BY preferredName, surname'; break;
                    case 'rollGroup':       $orderBy = 'ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName'; break;
                    case 'absent':
                    default:                $orderBy = 'ORDER BY attendance, surname, preferredName'; break;
                }

                $data = array('date' => $date, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonActivity.name AS activity, gibbonActivity.provider, gibbonPerson.surname, gibbonPerson.preferredName, gibbonRollGroup.name AS rollGroup,
                        (CASE WHEN gibbonActivityAttendance.gibbonActivityAttendanceID IS NULL THEN 'Absent' ELSE 'Present' END) as attendance
                        FROM gibbonActivity
                        JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID)
                        JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonActivity.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                        LEFT JOIN gibbonActivityAttendance
                            ON (gibbonActivityAttendance.gibbonActivityID=gibbonActivity.gibbonActivityID
                                AND (gibbonActivityAttendance.attendance LIKE CONCAT('%', gibbonPerson.gibbonPersonID, '%' ))
                            )
                        WHERE gibbonActivity.gibbonActivityID IN (".implode(',', $gibbonActivityIDList).")
                        AND gibbonPerson.status='Full'
                        AND gibbonActivityStudent.status='Accepted'
                        AND (gibbonActivityAttendance.date=:date OR gibbonActivityAttendance.date IS NULL)
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                ";

                $sql .= $orderBy;

                $attendanceResult = $connection2->prepare($sql);
                $attendanceResult->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if (!$attendanceResult || $attendanceResult->rowCount() == 0) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo '<table cellspacing="0" class="fullWidth colorOddEven" >';
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Activity');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Provider');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Attendance');
                echo '</th>';
                echo '</tr>';

                while ($row = $attendanceResult->fetch()) {
                    // ROW
                    echo '<tr class="'.(($row['attendance'] == 'Absent')? 'error' : '').'">';
                    echo '<td>';
                        echo $row['activity'];
                    echo '</td>';
                    echo '<td>';
                        echo $row['provider'];
                    echo '</td>';
                    echo '<td>';
                        echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                    echo '</td>';
                    echo '<td>';
                        echo $row['rollGroup'];
                    echo '</td>';
                    echo '<td>';
                        echo __($guid, $row['attendance']);
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            }
        }
    }
}
