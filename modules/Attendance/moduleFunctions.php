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

//Get's a count of absent days for specified student between specified dates (YYYY-MM-DD, inclusive). Return of FALSE means there was an error, or no data
function getAbsenceCount($guid, $gibbonPersonID, $connection2, $dateStart, $dateEnd, $gibbonCourseClassID = 0)
{
    $queryFail = false;

    global $gibbon, $session, $pdo;
    require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.
    try {
        if (!empty($gibbonCourseClassID)) {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonAttendanceLogPerson.*, gibbonSchoolYearSpecialDay.type AS specialDay FROM gibbonAttendanceLogPerson
                    LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=gibbonAttendanceLogPerson.date AND gibbonSchoolYearSpecialDay.type='School Closure')
                WHERE gibbonPersonID=:gibbonPersonID AND context='Class' AND gibbonCourseClassID=:gibbonCourseClassID AND (gibbonAttendanceLogPerson.date BETWEEN :dateStart AND :dateEnd) GROUP BY gibbonAttendanceLogPerson.date ORDER BY gibbonAttendanceLogPerson.date, timestampTaken";
        } else {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);
            $sql = "SELECT gibbonAttendanceLogPerson.*, gibbonSchoolYearSpecialDay.type AS specialDay FROM gibbonAttendanceLogPerson
                    LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=gibbonAttendanceLogPerson.date AND gibbonSchoolYearSpecialDay.type='School Closure')
                WHERE gibbonPersonID=:gibbonPersonID AND (gibbonAttendanceLogPerson.date BETWEEN :dateStart AND :dateEnd) GROUP BY gibbonAttendanceLogPerson.date ORDER BY gibbonAttendanceLogPerson.date, timestampTaken";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
        $queryFail = true;
    }

    if ($queryFail) {
        return false;
    } else {
        $absentCount = 0;
        if ($result->rowCount() >= 0) {
            $endOfDays = array();
            $dateCurrent = '';
            $dateLast = '';
            $count = -1;

            //Scan through all records, saving the last record for each day
            while ($row = $result->fetch()) {
                if ($row['specialDay'] != 'School Closure') {
                    $dateCurrent = $row['date'];
                    if ($dateCurrent != $dateLast) {
                        ++$count;
                    }
                    $endOfDays[$count] = $row['type'];
                    $dateLast = $dateCurrent;
                }
            }

            //Scan though all of the end of days records, counting up days ending in absent
            if (count($endOfDays) >= 0) {
                foreach ($endOfDays as $endOfDay) {
                    if ( $attendance->isTypeAbsent($endOfDay) ) {
                        ++$absentCount;
                    }
                }
            }
        }

        return $absentCount;
    }
}

//Get last N school days from currentDate within the last 100
function getLastNSchoolDays( $guid, $connection2, $date, $n = 5, $inclusive = false ) {


    $timestamp = dateConvertToTimestamp($date);
    if ($inclusive == true)  $timestamp += 86400;

    $count = 0;
    $spin = 1;
    $max = max($n, 100);
    $lastNSchoolDays = array();
    while ($count < $n and $spin <= $max) {
        $date = date('Y-m-d', ($timestamp - ($spin * 86400)));
        if (isSchoolOpen($guid, $date, $connection2 )) {
            $lastNSchoolDays[$count] = $date;
            ++$count;
        }
        ++$spin;
    }

    return $lastNSchoolDays;
}

//Get's a count of late days for specified student between specified dates (YYYY-MM-DD, inclusive). Return of FALSE means there was an error.
function getLatenessCount($guid, $gibbonPersonID, $connection2, $dateStart, $dateEnd)
{
    $queryFail = false;

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);
        $sql = "SELECT count(*) AS count FROM gibbonAttendanceLogPerson p, gibbonAttendanceCode c WHERE c.scope='Onsite - Late' AND p.gibbonPersonID=:gibbonPersonID AND p.date>=:dateStart AND p.date<=:dateEnd AND p.type=c.name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $queryFail = true;
    }

    if ($queryFail) {
        return false;
    } else {
        $row = $result->fetch();
        return $row['count'];
    }
}


//$dateStart and $dateEnd refer to the students' first and last day at the school, not the range of dates for the report
function report_studentHistory($guid, $gibbonPersonID, $print, $printURL, $connection2, $dateStart, $dateEnd)
{
    global $gibbon, $session, $pdo;
    require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    $attendanceByPersonAccessible = isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php');

    $output = '';

    if ($print) {
        echo "<div class='linkTop'>";
        echo "<a target=_blank href='$printURL'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'firstDay' => date('Y-m-d'));
        $sql = 'SELECT name, firstDay, lastDay, gibbonSchoolYearTermID FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND firstDay<=:firstDay';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'There are no records to display.');
        $output .= '</div>';
    } else {
        $countSchoolDays = 0;
        $countAbsent = 0;
        $countPresent = 0;
        $countTypes = array();
        $countReasons = array();

        while ($row = $result->fetch()) {
            $output .= '<h4>';
            $output .= $row['name'];
            $output .= '</h4>';
            list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $row['firstDay']);
            $firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
            list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $row['lastDay']);
            $lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);

            //Count back to first Monday before first day
            $startDayStamp = $firstDayStamp;
            while (date('D', $startDayStamp) != 'Mon') {
                $startDayStamp = $startDayStamp - 86400;
            }

            //Count forward to first Sunday after last day
            $endDayStamp = $lastDayStamp;
            while (date('D', $endDayStamp) != 'Sun') {
                $endDayStamp = $endDayStamp + 86400;
            }

            //Get the special days
            try {
                $dataSpecial = array('gibbonSchoolYearTermID' => $row['gibbonSchoolYearTermID']);
                $sqlSpecial = "SELECT name, date FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID AND type='School Closure' ORDER BY date";
                $resultSpecial = $connection2->prepare($sqlSpecial);
                $resultSpecial->execute($dataSpecial);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            $rowSpecial = null;
            if ($resultSpecial->rowCount() > 0) {
                $rowSpecial = $resultSpecial->fetch();
            }

            // Check which days are school days
            $days = array();
            $days['Mon'] = 'Y';
            $days['Tue'] = 'Y';
            $days['Wed'] = 'Y';
            $days['Thu'] = 'Y';
            $days['Fri'] = 'Y';
            $days['Sat'] = 'Y';
            $days['Sun'] = 'Y';
            $days['count'] = 7;
            try {
                $dataDays = array();
                $sqlDays = "SELECT nameShort FROM gibbonDaysOfWeek WHERE schoolDay='N'";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            // Mark non-school days as N
            while ($rowDays = $resultDays->fetch()) {
                $day = $rowDays['nameShort'];
                if ( isset($days[$day]) ) {
                    $days[$day] = 'N';
                    --$days['count'];
                }
            }

            $days['count'];
            $count = 0;
            $weeks = 2;

            $output .= "<table class='mini historyCalendar' cellspacing='0' style='width: 100%'>";
            $output .= "<tr class='head'>";
            for ($w = 0; $w < $weeks; ++$w) {
                if ($days['Mon'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Mon');
                    $output .= '</th>';
                }
                if ($days['Tue'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Tue');
                    $output .= '</th>';
                }
                if ($days['Wed'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Wed');
                    $output .= '</th>';
                }
                if ($days['Thu'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Thu');
                    $output .= '</th>';
                }
                if ($days['Fri'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Fri');
                    $output .= '</th>';
                }
                if ($days['Sat'] == 'Y') {
                    $output .= "<th style='width: 14px'>";
                    $output .= __($guid, 'Sat');
                    $output .= '</th>';
                }
                if ($days['Sun'] == 'Y') {
                    $output .= "<th style='width: 15px'>";
                    $output .= __($guid, 'Sun');
                    $output .= '</th>';
                }
            }
            $output .= '</tr>';

            //Make sure we are not showing future dates
            $now = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $end = $endDayStamp;
            if ($now < $endDayStamp) {
                $end = $now;
            }
            //Display grid
            for ($i = $startDayStamp;$i <= $end;$i = $i + 86400) {
                if ($days[date('D', $i)] == 'Y') {
                    if (($count % ($days['count'] * $weeks)) == 0 and $days[date('D', $i)] == 'Y') {
                        $output .= "<tr style='height: 45px'>";
                    }

                    //Before student started at school
                    if ($dateStart != '' and date('Y-m-d', $i) < $dateStart) {
                        $output .= "<td class='dayClosed'>";
                        $output .= date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>';
                        $output .= __($guid, 'Before Start Date');
                        $output .= '</td>';
                        ++$count;
                    }
                    //After student left school
                    elseif ($dateEnd != '' and date('Y-m-d', $i) > $dateEnd) {
                        $output .= "<td class='dayClosed'>";
                        $output .= date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>';
                        $output .= __($guid, 'After End Date');
                        $output .= '</td>';
                        ++$count;
                    }
                    //Student attending school
                    else {
                        $specialDayStamp = null;
                        if ($rowSpecial != null) {
                            if ($rowSpecial == true) {
                                list($specialDayYear, $specialDayMonth, $specialDayDay) = explode('-', $rowSpecial['date']);
                                $specialDayStamp = mktime(0, 0, 0, $specialDayMonth, $specialDayDay, $specialDayYear);
                            }
                        }

                        if ($i < $firstDayStamp or $i > $lastDayStamp) {
                            $output .= "<td class='dayClosed'>";
                            $output .= '</td>';
                            ++$count;

                            if ($i == $specialDayStamp) {
                                $rowSpecial = $resultSpecial->fetch();
                            }
                        } else {
                            if ($i == $specialDayStamp) {
                                $output .= "<td class='dayClosed'>";
                                $output .= $rowSpecial['name'];
                                $output .= '</td>';
                                ++$count;
                                $rowSpecial = $resultSpecial->fetch();
                            } else {
                                if ($days[date('D', $i)] == 'Y') {
                                    ++$countSchoolDays;

                                    $log = array();
                                    $logCount = 0;
                                    try {
                                        $dataLog = array('date' => date('Y-m-d', $i), 'gibbonPersonID' => $gibbonPersonID);
                                        $sqlLog = 'SELECT gibbonAttendanceLogPerson.type, gibbonAttendanceLogPerson.reason FROM gibbonAttendanceLogPerson, gibbonAttendanceCode WHERE gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name AND date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY timestampTaken DESC';
                                        $resultLog = $connection2->prepare($sqlLog);
                                        $resultLog->execute($dataLog);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($resultLog->rowCount() < 1) {
                                        $class = 'dayNoData';
                                    } else {
                                        while ($rowLog = $resultLog->fetch()) {
                                            $log[$logCount][0] = $rowLog['type'];
                                            $log[$logCount][1] = $rowLog['reason'];

                                            if ($rowLog['type'] != 'Present') @$countTypes[ $rowLog['type'] ]++;
                                            if ($rowLog['reason'] != '') @$countReasons[ $rowLog['reason'] ]++;

                                            ++$logCount;
                                        }

                                        if ( $attendance->isTypeAbsent($log[0][0])) {
                                            ++$countAbsent;
                                            $class = 'dayAbsent';
                                            $textClass = 'highlightAbsent';
                                        } else {
                                            ++$countPresent;
                                            $class = 'dayPresent';
                                            $textClass = 'highlightPresent';
                                        }
                                        if ($log[0][1] != '') {
                                            $title = "title='".$log[0][1]."'";
                                        } else {
                                            $title = '';
                                        }
                                    }
                                    $formattedDate = date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i);

                                    $output .= "<td class='day $class'>";
                                    if ($attendanceByPersonAccessible) {
                                        $output .= '<a href="index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID='.$gibbonPersonID.'&currentDate='.$formattedDate.'">';
                                    }
                                    $output .= $formattedDate.'<br/>';
                                    if (count($log) > 0) {
                                        $output .= "<span style='font-weight: bold' $title>".$log[0][0].'</span><br/>';

                                        for ($x = count($log); $x >= 0; --$x) {
                                            if (isset($log[$x][0])) {
                                                $textClass = $attendance->isTypeAbsent($log[$x][0])? 'highlightAbsent' : 'highlightPresent';
                                                $output .= '<span class="'.$textClass.'">';
                                                $output .= $attendance->getAttendanceCodeByType( $log[$x][0] )['nameShort'];
                                                $output .= '</span>';
                                            }
                                            if ($x != 0 and $x != count($log)) {
                                                $output .= ' : ';
                                            }
                                        }
                                    }
                                    if ($attendanceByPersonAccessible) {
                                        $output .= '</a>';
                                    }
                                    $output .= '</td>';
                                    ++$count;
                                }
                            }
                        }
                    }

                    if (($count % ($days['count'] * $weeks)) == 0 and $days[date('D', $i)] == 'Y') {
                        $output .= '</tr>';
                    }
                }
            }

            $output .= '</table>';
        }
    }

    echo "<table cellspacing='0'>";
    echo '<tr>';
    echo "<td style='vertical-align: top; width: 380px'>";
    echo '<h3>';
    echo __($guid, 'Summary');
    echo '</h3>';
    echo '<p>';
    if (isset($countSchoolDays) and isset($countPresent) and isset($countAbsent)) {
        if ($countSchoolDays != ($countPresent + $countAbsent)) {
            echo '<i>'.__($guid, 'It appears that this student is missing attendance data for some school days:').'</i><br/>';
            echo '<br/>';
        }
        echo '<b>'.__($guid, 'Total number of school days to date:')." $countSchoolDays</b><br/>";
        echo __($guid, 'Total number of school days attended:')." $countPresent<br/>";
        echo __($guid, 'Total number of school days absent:')." $countAbsent<br/>";

        if ( count($countTypes) > 0 ) {
            echo '<br/><b>'.__($guid, 'Type').":</b><br/>";
            foreach ($countTypes as $typeName => $count ) {
                echo '<span style="width:180px;display:inline-block;">'.__($guid, $typeName)."</span>$count<br/>";
            }
        }

        if ( count($countReasons) > 0 ) {
            echo '<br/><b>'.__($guid, 'Reason').":</b><br/>";
            foreach ($countReasons as $reasonName => $count ) {
                echo '<span style="width:180px;display:inline-block;">'.__($guid, $reasonName)."</span>$count<br/>";
            }
        }
    } else {
        echo __($guid, 'NA');
    }
    echo '</p>';
    echo '</td>';
    echo "<td style='vertical-align: top'>";
    echo '<h3>';
    echo __($guid, 'Key');
    echo '</h2>';

    // Student History Legend
    echo "<table class='mini historyCalendar historyCalendarKey' cellspacing='8' style='width: 100%'>";
    echo '<tr>';
    echo '<td class="legend">'.__($guid, 'School Closed').'</td>';
    echo '<td class="legend">'.__($guid, 'Present').' '.__($guid, 'Day').'</td>';
    echo '<td class="legend">'.__($guid, 'Absent').' '.__($guid, 'Day').'</td>';
    echo '<td class="legend">'.__($guid, 'No Data').' '.__($guid, 'Day').'</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="day dayClosed">'.__($guid, 'School Closed').'</td>';
    echo '<td class="day dayPresent">'.date($_SESSION[$guid]['i18n']['dateFormatPHP'], 1487667393).'<br/><b>'.__($guid, 'Left - Early').'</b><br/>P : L</td>';
    echo '<td class="day dayAbsent">'.date($_SESSION[$guid]['i18n']['dateFormatPHP'], 1487767393).'<br/><b>'.__($guid, 'Absent').'</b><br/>A</td>';
    echo '<td class="day dayNoData">'.__($guid, 'No Data').'</td>';
    echo '</tr>';

    try {
        $data = array();
        $sql = 'SELECT name, nameShort FROM gibbonAttendanceCode ORDER BY sequenceNumber ASC, name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result && $result->rowCount() > 0) {
        echo '<tr>';
        echo '<td colspan="1">';
            echo '<b class="legend">'.__($guid, 'End-of-Day Status').'</b>';
        echo '</td>';
        echo '<td colspan="3" style="border-left: 0px;">';
            echo '<b class="legend">'.__($guid, 'Detailed Day Log Where:').'</b>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '</td>';
        echo '<td colspan="4">';

        echo '<div class="arrow-one">&#8599;</div>';
        echo '<div class="arrow-two">&#8599;</div>';

        echo '<ul>';
        while ($code = $result->fetch()) {
            $class = ($attendance->isTypeAbsent($code['name']))? 'highlightAbsent' : 'highlightPresent';
            echo sprintf('<li><span class="%1$s">%2$s</span> = %3$s</li>', $class, $code['nameShort'], __($guid, $code['name']) );
        }
        echo '</ul>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';

    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo $output;
}

function num2alpha($n)
{
    for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
        $r = chr($n % 26 + 0x41).$r;
    }

    return $r;
}

function getColourArray()
{
    $return = array();

    $return[] = '255, 99, 132';
    $return[] = '54, 162, 235';
    $return[] = '255, 206, 86';
    $return[] = '153, 102, 255';
    $return[] = '75, 192, 192';
    $return[] = '255, 159, 64';
    $return[] = '152, 221, 95';

    return $return;
}
