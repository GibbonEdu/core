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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_formGroupsNotRegistered_byDate_print.php') == false) {
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
        echo __('Form Groups Not Registered').', '.Format::date($dateStart).'-'.Format::date($dateEnd);
    } else {
        echo __('Form Groups Not Registered').', '.Format::date($dateStart);
    }
    echo '</h2>';

    //Produce array of attendance data

        $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
        $sql = 'SELECT date, gibbonFormGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogFormGroup WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    $log = array();
    while ($row = $result->fetch()) {
        $log[$row['gibbonFormGroupID']][$row['date']] = true;
    }


        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT gibbonFormGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND attendance='Y' ORDER BY LENGTH(name), name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ( count($lastNSchoolDays) == 0 ) {
        echo "<div class='error'>";
        echo __('School is closed on the specified date, and so attendance information cannot be recorded.');
        echo '</div>';
    } else if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else if ($dateStart > $today || $dateEnd > $today) {
        echo "<div class='error'>";
        echo __('The specified date is in the future: it must be today or earlier.');
        echo '</div>';
    } else {
        //Produce array of form groups
        $formGroups = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='javascript:window.print()'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
        echo '</div>';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Form Group');
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

        foreach ($formGroups as $row) {

            //Output row only if not registered on specified date
            if ( isset($log[$row['gibbonFormGroupID']]) == false || count($log[$row['gibbonFormGroupID']]) < count($lastNSchoolDays) ) {
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
                            if (isset($log[$row['gibbonFormGroupID']][$lastNSchoolDays[$i]]) == false) {
                                //$class = 'highlightNoData';
                                $class = 'highlightAbsent';
                            } else {
                                $link = './index.php?q=/modules/Attendance/attendance_take_byFormGroup.php&gibbonFormGroupID='.$row['gibbonFormGroupID'].'&currentDate='.$lastNSchoolDays[$i];
                                $class = 'highlightPresent';
                            }

                            echo "<td class='$class' style='padding: 12px !important;'>";
                            if ($link != '') {
                                echo "<a href='$link'>";
                                echo Format::date($lastNSchoolDays[$i], 'd').'<br/>';
                                echo "<span>".Format::monthName($lastNSchoolDays[$i], true).'</span>';
                                echo '</a>';
                            } else {
                                echo Format::date($lastNSchoolDays[$i], 'd').'<br/>';
                                echo "<span>".Format::monthName($lastNSchoolDays[$i], true).'</span>';
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
            echo __('All form groups have been registered.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
