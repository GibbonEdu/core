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

use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_rollGroupsNotRegistered_byDate_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

    $today = date('Y-m-d');

    $dateEnd = (isset($_GET['dateEnd']))? dateConvert($guid, $_GET['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_GET['dateStart']))? dateConvert($guid, $_GET['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -4 days') );

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
        echo __('Roll Groups Not Registered').', '.dateConvertBack($guid, $dateStart).'-'.dateConvertBack($guid, $dateEnd);
    } else {
        echo __('Roll Groups Not Registered').', '.dateConvertBack($guid, $dateStart);
    }
    echo '</h2>';

    //Produce array of attendance data
    
        $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
        $sql = 'SELECT date, gibbonRollGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogRollGroup WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    $log = array();
    while ($row = $result->fetch()) {
        $log[$row['gibbonRollGroupID']][$row['date']] = true;
    }

    
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonRollGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND attendance='Y' ORDER BY LENGTH(name), name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ( count($lastNSchoolDays) == 0 ) {
        echo "<div class='error'>";
        echo __('School is closed on the specified date, and so attendance information cannot be recorded.');
        echo '</div>';
    } else if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else if ($dateStart > $today || $dateEnd > $today) {
        echo "<div class='error'>";
        echo __('The specified date is in the future: it must be today or earlier.');
        echo '</div>';
    } else {
        //Produce array of roll groups
        $rollGroups = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='javascript:window.print()'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Roll Group');
        echo '</th>';
        echo '<th >';
        echo __('Date');
        echo '</th>';
        echo '<th width="164px">';
        echo __('History');
        echo '</th>';
        echo '<th>';
        echo __('Tutor');
        echo '</th>';
        echo '</tr>';

        $count = 0;

        foreach ($rollGroups as $row) {

            //Output row only if not registered on specified date
            if ( isset($log[$row['gibbonRollGroupID']]) == false || count($log[$row['gibbonRollGroupID']]) < count($lastNSchoolDays) ) {
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr>";
                echo '<td>';
                echo $row['name'];
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
                            if (isset($log[$row['gibbonRollGroupID']][$lastNSchoolDays[$i]]) == false) {
                                //$class = 'highlightNoData';
                                $class = 'highlightAbsent';
                            } else {
                                $link = './index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$row['gibbonRollGroupID'].'&currentDate='.$lastNSchoolDays[$i];
                                $class = 'highlightPresent';
                            }

                            echo "<td class='$class' style='padding: 12px !important;'>";
                            if ($link != '') {
                                echo "<a href='$link'>";
                                echo Format::dateReadable($lastNSchoolDays[$i], '%d').'<br/>';
                                echo "<span>".Format::dateReadable($lastNSchoolDays[$i], '%b').'</span>';
                                echo '</a>';
                            } else {
                                echo Format::dateReadable($lastNSchoolDays[$i], '%d').'<br/>';
                                echo "<span>".Format::dateReadable($lastNSchoolDays[$i], '%b').'</span>';
                            }
                            echo '</td>';
                        }

                        // Wrap to a new line every 10 dates
                        if (  ($historyCount+1) % 10 == 0 ) {
                            echo '</tr><tr>';
                        }

                        $historyCount++;
                    }

                    echo '</tr>';
                    echo '</table>';

                echo '</td>';
                echo '<td>';
                if ($row['gibbonPersonIDTutor'] == '' and $row['gibbonPersonIDTutor2'] == '' and $row['gibbonPersonIDTutor3'] == '') {
                    echo '<i>Not set</i>';
                } else {
                    
                        $dataTutor = array('gibbonPersonID1' => $row['gibbonPersonIDTutor'], 'gibbonPersonID2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonID3' => $row['gibbonPersonIDTutor3']);
                        $sqlTutor = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3';
                        $resultTutor = $connection2->prepare($sqlTutor);
                        $resultTutor->execute($dataTutor);

                    while ($rowTutor = $resultTutor->fetch()) {
                        echo Format::name('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', true, true).'<br/>';
                    }
                }
                echo '</td>';
                echo '</tr>';
            }
        }

        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=4>';
            echo __('All roll groups have been registered.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
