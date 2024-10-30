<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_courseClassesNotRegistered_byDate_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

    $today = date('Y-m-d');

    $dateEnd = (isset($_GET['dateEnd']))? Format::dateConvert($_GET['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_GET['dateStart']))? Format::dateConvert($_GET['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -4 days') );

    $datediff = strtotime($dateEnd) - strtotime($dateStart);
    $daysBetweenDates = floor($datediff / (60 * 60 * 24)) + 1;

    $lastSetOfSchoolDays = getLastNSchoolDays($guid, $connection2, $dateEnd, $daysBetweenDates, true);

    $lastNSchoolDays = array();
    for($i = 0; $i < count($lastSetOfSchoolDays); $i++) {
        if ( $lastSetOfSchoolDays[$i] >= $dateStart  ) $lastNSchoolDays[] = $lastSetOfSchoolDays[$i];
    }

    //Proceed!
    echo '<h2>';
    if ($dateStart != $dateEnd) {
        echo __('Classes Not Registered').', '.Format::date($dateStart).'-'.Format::date($dateEnd);
    } else {
        echo __('Classes Not Registered').', '.Format::date($dateStart);
    }
    echo '</h2>';

    //Produce array of attendance data

        $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0]);
        $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date";

        $result = $connection2->prepare($sql);
        $result->execute($data);
    $log = array();
    while ($row = $result->fetch()) {
        $log[$row['gibbonCourseClassID']][$row['date']] = true;
    }

    // Produce an array of scheduled classes

        $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
        $sql = "SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) WHERE gibbonCourseClass.attendance = 'Y' AND gibbonTTDayDate.date>=:dateStart AND gibbonTTDayDate.date<=:dateEnd ORDER BY gibbonTTDayDate.date";

        $result = $connection2->prepare($sql);
        $result->execute($data);
    $tt = array();
    while ($row = $result->fetch()) {
        $tt[$row['gibbonCourseClassID']][$row['date']] = true;
    }



        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID') );
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort, (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance = 'Y' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ( count($lastNSchoolDays) == 0 ) {
        echo "<div class='error'>";
        echo __('School is closed on the specified date, and so attendance information cannot be recorded.');
        echo '</div>';
    } else if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    }
    else if ($dateEnd > $today) {
        echo "<div class='error'>";
        echo __('The specified date is in the future: it must be today or earlier.');
        echo '</div>';
    } else {
        //Produce array of form groups
        $classes = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='javascript:window.print()'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
        echo '</div>';

        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th width="140px">';
        echo __('Class');
        echo '</th>';
        echo '<th>';
        echo __('Date');
        echo '</th>';
        echo '<th width="164px">';
        echo __('History');
        echo '</th>';
        echo '<th>';
        echo __('Teacher');
        echo '</th>';
        echo '</tr>';

        $count = 0;

        //Loop through each form group
        foreach ($classes as $row) {

            // Skip classes with no students
            if ($row['studentCount'] <= 0) continue;

            //Output row only if not registered on specified date, and timetabled for that day
            if (isset($tt[$row['gibbonCourseClassID']]) == true && (isset($log[$row['gibbonCourseClassID']]) == false ||
                count($log[$row['gibbonCourseClassID']]) < min(count($lastNSchoolDays), count($tt[$row['gibbonCourseClassID']])) ) ) {
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr>";
                echo '<td>';
                echo $row['courseShort'].'.'.$row['class'];
                echo '</td>';
                echo '<td>';
                echo Format::dateRangeReadable($dateStart, $dateEnd);
                echo '</td>';
                echo '<td style="padding: 0;">';

                    echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
                    echo '<tr>';
                    $historyCount = 0;
                    for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {
                        $link = '';
                        if ($i > ( count($lastNSchoolDays) - 1)) {
                            echo "<td class='highlightNoData'>";
                            echo '<i>'.__('NA').'</i>';
                            echo '</td>';
                        } else {

                            if ( isset($log[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true ) {
                                $class = 'highlightPresent';
                            } else {
                                if (isset($tt[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
                                    $class = 'highlightAbsent';
                                } else {
                                    $class = 'highlightNoData';
                                }
                            }

                            echo "<td class='$class' style='padding: 12px !important;'>";
                            echo Format::date($lastNSchoolDays[$i], 'd').'<br/>';
                            echo "<span>".Format::monthName($lastNSchoolDays[$i], true).'</span>';
                            echo '</td>';

                            // Wrap to a new line every 10 dates
                            if (  ($historyCount+1) % 10 == 0 ) {
                                echo '</tr><tr>';
                            }

                            $historyCount++;
                        }
                    }

                    echo '</tr>';
                    echo '</table>';

                echo '</td>';
                echo '<td>';

                $dataTutor = array('gibbonCourseClassID' => $row['gibbonCourseClassID'] );
                $sqlTutor = 'SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.role = "Teacher"';
                $resultTutor = $connection2->prepare($sqlTutor);
                $resultTutor->execute($dataTutor);

                if ($resultTutor->rowCount() > 0) {
                    while ($rowTutor = $resultTutor->fetch()) {
                        echo Format::name('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', true, true).'<br/>';
                    }
                }

                echo '</td>';
                echo '</tr>';
            }
        }

        if ($count == 0) {
            echo "<tr";
            echo '<td colspan=3>';
            echo __('All classes have been registered.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
