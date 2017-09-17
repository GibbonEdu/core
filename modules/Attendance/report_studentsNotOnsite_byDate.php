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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotOnsite_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Students Not Onsite').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    if (isset($_GET['currentDate']) == false) {
        $currentDate = date('Y-m-d');
    } else {
        $currentDate = dateConvert($guid, $_GET['currentDate']);
    }

    $allStudents = !empty($_GET["allStudents"])? 1 : 0;
    $sort = !empty($_GET['sort'])? $_GET['sort'] : 'surname';

    require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_studentsNotOnsite_byDate.php");

    $row = $form->addRow();
        $row->addLabel('currentDate', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('currentDate')->setValue(dateConvertBack($guid, $currentDate))->isRequired();

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'preferredName' => __('Preferred Name'), 'rollGroup' => __('Roll Group')))->selected($sort)->isRequired();

    $row = $form->addRow();
        $row->addLabel('allStudents', __('All Students'))->description('Include all students, even those where attendance has not yet been recorded.');
        $row->addCheckbox('allStudents')->checked($allStudents);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($currentDate != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
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
            $count = 0;
            while ($row = $result->fetch()) {
                $currentStudent = $row['gibbonPersonID'];
                if ( $attendance->isTypeOnsite($row['type']) and $currentStudent != $lastStudent) {
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

                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as rollGroupName, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ";

                $sql .= $orderBy;

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
                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_studentsNotOnsite_byDate_print.php&currentDate='.dateConvertBack($guid, $currentDate)."&allStudents=" . $allStudents . "&sort=" . $sort . "'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                echo '</div>';

                $lastPerson = '';

                echo '<table cellspacing="0" class="fullWidth colorOddEven" >';
                echo '<tr class="head">';
                echo '<th>';
                echo __($guid, 'Count');
                echo '</th>';
                echo '<th style="width:80px">';
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

                while ($row = $result->fetch()) {
                    if (isset($log[$row['gibbonPersonID']]) == false) {

                        try {
                            $dataAttendance = array('date' => $currentDate, 'gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlAttendance = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC';
                            $resultAttendance = $connection2->prepare($sqlAttendance);
                            $resultAttendance->execute($dataAttendance);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        // Skip rows with no record if we're not displaying all students
                        if ($resultAttendance->rowCount()<1 && $allStudents == FALSE) {
                            continue;
                        }

                        $count ++;

                        // Row
                        echo "<tr>";
                        echo '<td>';
                            echo $count;
                        echo '</td>';
                        echo '<td>';
                            echo $row['rollGroupName'];
                        echo '</td>';
                        echo '<td>';
                            echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                        echo '</td>';
                        echo '<td>';
                        $rowRollAttendance = null;

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
                if ($result->rowCount() == 0) {
                    echo "<tr>";
                    echo '<td colspan=5>';
                    echo __($guid, 'All students are present.');
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>
