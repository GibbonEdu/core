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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_courseClassesNotRegistered_byDate_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {

    $today = date('Y-m-d');

    if (isset($_GET['date']) == false) {
        $date = date('Y-m-d');
    } else {
        $date = dateConvert($guid, $_GET['date']);
    }

    $last5SchoolDays = getLastNSchoolDays($guid, $connection2, $date, 5);

    //Proceed!
    echo '<h2>';
        echo __($guid, 'Classes Not Registered').', '.dateConvertBack($guid, $date);
    echo '</h2>';

    //Produce array of attendance data
    try {
        $data = array('attendanceDate' => $date);
        $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date=:attendanceDate ORDER BY date";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $log = array();
    while ($row = $result->fetch()) {
        $log[$row['date']][$row['gibbonCourseClassID']] = true;
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'attendanceDate' => $date );
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort, (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:attendanceDate AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance = 'Y' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    }
    else if ($date > $today) {
        echo "<div class='error'>";
        echo __($guid, 'The specified date is in the future: it must be today or earlier.');
        echo '</div>';
    } else {
        //Produce array of roll groups
        $classes = $result->fetchAll();

        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th width="140px">';
        echo __($guid, 'Class');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Date');
        echo '</th>';
        echo '<th width="164px">';
        echo __($guid, 'History');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Tutor');
        echo '</th>';
        echo '</tr>';

        $count = 0;

        //Loop through each date
        if (isSchoolOpen($guid, $date, $connection2, true)) {
            //Loop through each roll group
            foreach ($classes as $row) {

                // Skip classes with no students
                if ($row['studentCount'] <= 0) continue;

                //Output row only if not registered on specified date
                if (isset($log[$date][$row['gibbonCourseClassID']]) == false) {
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr>";
                    echo '<td>';
                    echo $row['courseShort'].'.'.$row['class'];
                    echo '</td>';
                    echo '<td>';
                    echo dateConvertBack($guid, $date);
                    echo '</td>';
                    echo '<td style="padding: 0;">';
                    
                        echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
                        echo '<tr>';
                        for ($i = 4; $i >= 0; --$i) {
                            $link = '';
                            if ($i > ( count($last5SchoolDays) - 1)) {
                                echo "<td class='highlightNoData'>";
                                echo '<i>'.__($guid, 'NA').'</i>';
                                echo '</td>';
                            } else {
                                $currentDayTimestamp = dateConvertToTimestamp($last5SchoolDays[$i]);
                                
                                try {
                                    $dataLast5SchoolDays = array('gibbonCourseClassID' => $row['gibbonCourseClassID'], 'attendanceDate' => $last5SchoolDays[$i] );
                                    $sqlLast5SchoolDays = 'SELECT gibbonTTDayDate.date, UNIX_TIMESTAMP(gibbonAttendanceLogCourseClass.timestampTaken) as timestamp FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) LEFT JOIN gibbonAttendanceLogCourseClass ON (gibbonAttendanceLogCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID AND gibbonAttendanceLogCourseClass.date = gibbonTTDayDate.date) WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonTTDayDate.date=:attendanceDate LIMIT 1';
                                    $resultLast5SchoolDays = $pdo->executeQuery($dataLast5SchoolDays, $sqlLast5SchoolDays);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultLast5SchoolDays->rowCount() == 0) {
                                    $class = 'highlightNoData';
                                    
                                } else {

                                    $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.$last5SchoolDays[$i];
                                    $rowLast5SchoolDays = $resultLast5SchoolDays->fetch();

                                    if ( empty($rowLast5SchoolDays['timestamp']) ) {
                                        $class = 'highlightAbsent';
                                    } else {
                                        $class = 'highlightPresent';
                                    }
                                    
                                }

                                echo "<td class='$class' style='padding: 12px !important;'>";
                                if ($link != '') {
                                    $title = (!empty($rowLast5SchoolDays['timestamp']))? __($guid, "Taken").' '.date('F j, Y, g:i a', $rowLast5SchoolDays['timestamp']) : '';
                                    echo "<a href='$link' title='".$title."'>";
                                    echo date('d', $currentDayTimestamp).'<br/>';
                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
                                    echo '</a>';
                                } else {
                                    echo date('d', $currentDayTimestamp).'<br/>';
                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
                                }
                                echo '</td>';
                            }
                        }


                        $currentDayTimestamp = dateConvertToTimestamp($date);
                        echo "<td class='highlightAbsent'>";
                            $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.$date;
                            echo "<a href='$link'>";
                            echo date('d', $currentDayTimestamp).'<br/>';
                            echo "<span>".date('M', $currentDayTimestamp).'</span>';
                            echo '</a>';
                        echo '</td>';

                        echo '</tr>';
                        echo '</table>';

                    echo '</td>';
                    echo '<td>';

                    try {
                        $dataTutor = array('gibbonCourseClassID' => $row['gibbonCourseClassID'] );
                        $sqlTutor = 'SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.role = "Teacher"';
                        $resultTutor = $connection2->prepare($sqlTutor);
                        $resultTutor->execute($dataTutor);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultTutor->rowCount() > 0) {
                        while ($rowTutor = $resultTutor->fetch()) {
                            echo formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', true, true).'<br/>';
                        }
                    }
                    
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
        

        if ($count == 0) {
            echo "<tr";
            echo '<td colspan=3>';
            echo __($guid, 'All classes have been registered.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
