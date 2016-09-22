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

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND date BETWEEN :dateStart AND :dateEnd ORDER BY date, timestampTaken';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
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
                $dateCurrent = $row['date'];
                if ($dateCurrent != $dateLast) {
                    ++$count;
                }
                $endOfDays[$count] = $row['type'];
                $dateLast = $dateCurrent;
            }

            //Scan though all of the end of days records, counting up days ending in absent
            if (count($endOfDays) >= 0) {
                foreach ($endOfDays as $endOfDay) {
                    if ($endOfDay == 'Absent') {
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
    $lastNSchoolDays = array();
    while ($count < $n and $spin <= 100) {
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
        $sql = "SELECT * FROM gibbonAttendanceLogPerson WHERE type='Present - Late' AND gibbonPersonID=:gibbonPersonID AND date>=:dateStart AND date<=:dateEnd";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $queryFail = true;
    }

    if ($queryFail) {
        return false;
    } else {
        return $result->rowCount();
    }
}

//$dateStart and $dateEnd refer to the students' first and last day at the school, not the range of dates for the report
function report_studentHistory($guid, $gibbonPersonID, $print, $printURL, $connection2, $dateStart, $dateEnd)
{

    require_once './modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView(NULL, NULL, NULL);

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
                        $output .= 'Before Start Date';
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
                                        $sqlLog = 'SELECT type, reason FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC';
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
                                            ++$logCount;
                                        }

                                        if ( $attendance->isTypeAbsent($log[0][0])) {
                                            ++$countAbsent;
                                            $class = 'dayAbsent';
                                        } else {
                                            ++$countPresent;
                                            $class = 'dayPresent';
                                        }
                                        if ($log[0][1] != '') {
                                            $title = "title='".$log[0][1]."'";
                                        } else {
                                            $title = '';
                                        }
                                    }
                                    $output .= "<td class='$class'>";
                                    $output .= date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>';
                                    if (count($log) > 0) {
                                        $output .= "<span style='font-weight: bold' $title>".$log[0][0].'</span><br/>';
                                        for ($x = count($log); $x >= 0; --$x) {
                                            if (isset($log[$x][0])) {
                                                $output .= $attendance->getAttendanceCodeByType( $log[$x][0] );
                                            }
                                            if ($x != 0 and $x != count($log)) {
                                                $output .= ' : ';
                                            }
                                        }
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
    echo "<td style='vertical-align: top; width: 410px'>";
    echo '<h3>';
    echo __($guid, 'Summary');
    echo '</h2>';
    echo '<p>';
    if (isset($countSchoolDays) and isset($countPresent) and isset($countAbsent)) {
        if ($countSchoolDays != ($countPresent + $countAbsent)) {
            echo '<i>'.__($guid, 'It appears that this student is missing attendance data for some school days:').'</i><br/>';
            echo '<br/>';
        }
        echo '<b>'.__($guid, 'Total number of school days to date:')." $countSchoolDays</b><br/>";
        echo __($guid, 'Total number of school days attended:')." $countPresent<br/>";
        echo __($guid, 'Total number of school days absent:')." $countAbsent<br/>";
    } else {
        echo __($guid, 'NA');
    }
    echo '</p>';
    echo '</td>';
    echo "<td style='vertical-align: top'>";
    echo '<h3>';
    echo __($guid, 'Key');
    echo '</h2>';
    echo '<p>';
    echo "<img style='border: 1px solid #eee' alt='Data Key' src='".$_SESSION[$guid]['absoluteURL']."/modules/Attendance/img/dataKey.png'>";
    echo '</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo $output;
}

