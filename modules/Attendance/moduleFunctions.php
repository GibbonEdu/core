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
function getAbsenceCount($guid, $gibbonPersonID, $connection2, $dateStart, $dateEnd)
{
    $queryFail = false;

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);
        $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=0 AND date>=:dateStart AND date<=:dateEnd ORDER BY date, timestampTaken';
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
    $output = '';

    if ($print) {
        echo "<div class='linkTop'>";
        echo "<a target=_blank href='$printURL'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
        echo '</div>';
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'firstDay' => date('Y-m-d'));
        $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND firstDay<=:firstDay';
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
                $sqlSpecial = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID AND type='School Closure' ORDER BY date";
                $resultSpecial = $connection2->prepare($sqlSpecial);
                $resultSpecial->execute($dataSpecial);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            $rowSpecial = null;
            if ($resultSpecial->rowCount() > 0) {
                $rowSpecial = $resultSpecial->fetch();
            }

            //Check which days are school days
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
                $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            while ($rowDays = $resultDays->fetch()) {
                if ($rowDays['nameShort'] == 'Mon') {
                    $days['Mon'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Tue') {
                    $days['Tue'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Wed') {
                    $days['Wed'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Thu') {
                    $days['Thu'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Fri') {
                    $days['Fri'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Sat') {
                    $days['Sat'] = 'N';
                    --$days['count'];
                } elseif ($rowDays['nameShort'] == 'Sun') {
                    $days['Sun'] = 'N';
                    --$days['count'];
                }
            }

            $days['count'];
            $count = 0;
            $weeks = 2;

            $output .= "<table class='mini' cellspacing='0' style='width: 100%'>";
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
                        $output .= "<td style='border: 1px solid #D65602; color: #D65602; background-color: #FFD2A9!important; text-align: center; font-size: 10px'>";
                        $output .= date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>';
                        $output .= 'Before Start Date';
                        $output .= '</td>';
                        ++$count;
                    }
                    //After student left school
                    elseif ($dateEnd != '' and date('Y-m-d', $i) > $dateEnd) {
                        $output .= "<td style='border: 1px solid #D65602; color: #D65602; background-color: #FFD2A9!important; text-align: center; font-size: 10px'>";
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
                            $output .= "<td style='border: 1px solid #aaa; color: #aaa; background-color: #ccc!important; text-align: center; font-size: 10px'>";
                            $output .= '</td>';
                            ++$count;

                            if ($i == $specialDayStamp) {
                                $rowSpecial = $resultSpecial->fetch();
                            }
                        } else {
                            if ($i == $specialDayStamp) {
                                $output .= "<td style='border: 1px solid #aaa; color: #aaa; background-color: #ccc!important; text-align: center; font-size: 10px'>";
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
                                        $sqlLog = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC';
                                        $resultLog = $connection2->prepare($sqlLog);
                                        $resultLog->execute($dataLog);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($resultLog->rowCount() < 1) {
                                        $extraStyle = 'border: 1px solid #555; color: #555; background-color: #eee; ';
                                    } else {
                                        while ($rowLog = $resultLog->fetch()) {
                                            $log[$logCount][0] = $rowLog['type'];
                                            $log[$logCount][1] = $rowLog['reason'];
                                            ++$logCount;
                                        }

                                        if ($log[0][0] == 'Absent') {
                                            ++$countAbsent;
                                            $extraStyle = 'border: 1px solid #c00; color: #c00; background-color: #F6CECB; ';
                                        } else {
                                            ++$countPresent;
                                            $extraStyle = 'border: 1px solid #390; color: #390; background-color: #D4F6DC; ';
                                        }
                                        if ($log[0][1] != '') {
                                            $title = "title='".$log[0][1]."'";
                                        } else {
                                            $title = '';
                                        }
                                    }
                                    $output .= "<td style='text-align: center; font-size: 10px; $extraStyle'>";
                                    $output .= date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>';
                                    if (count($log) > 0) {
                                        $output .= "<span style='font-weight: bold' $title>".$log[0][0].'</span><br/>';
                                        for ($x = count($log); $x >= 0; --$x) {
                                            if (isset($log[$x][0])) {
                                                if ($log[$x][0] == 'Present') {
                                                    $output .= 'P';
                                                } elseif ($log[$x][0] == 'Present - Late') {
                                                    $output .= 'PL';
                                                } elseif ($log[$x][0] == 'Present - Offsite') {
                                                    $output .= 'PS';
                                                } elseif ($log[$x][0] == 'Left') {
                                                    $output .= 'L';
                                                } elseif ($log[$x][0] == 'Left - Early') {
                                                    $output .= 'LE';
                                                } elseif ($log[$x][0] == 'Absent') {
                                                    $output .= 'A';
                                                }
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

function renderAttendanceTypeSelect( $guid, $connection2, $lastType = '', $name='type', $width='302px' ) {

    // Save in the session to prevent a ton of unessesary queries
    if ( empty($_SESSION[$guid]['attendanceTypes']) || !is_array($_SESSION[$guid]['attendanceTypes']) ) {
        
        $presentDescriptors = explode(',', getSettingByScope($connection2, 'Attendance', 'attendancePresentDescriptors') );
        $lateDescriptors = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceLateDescriptors') );
        $absentDescriptors = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceAbsentDescriptors') );

        $attendanceTypes = array_merge($presentDescriptors, $lateDescriptors, $absentDescriptors);

        if (!empty($attendanceTypes)) {
            $_SESSION[$guid]['attendanceTypes'] = $attendanceTypes;
        }
    }

    echo "<select style='float: none; width: $width; margin-bottom: 3px' name='$name' id='$name'>";

    if (!empty($_SESSION[$guid]['attendanceTypes']) && is_array($_SESSION[$guid]['attendanceTypes'])) {
        foreach ($_SESSION[$guid]['attendanceTypes'] as $attendanceType) {
            printf('<option value="%1$s" %2$s/>%1$s</option>', $attendanceType, (($lastType == $attendanceType)? 'selected' : '' ) );
        }
    }

    echo '</select>';
}

function renderAttendanceReasonSelect( $guid, $connection2, $lastReason = '', $name='reason', $width='302px' ) {

     // Save in the session to prevent a ton of unessesary queries
    if ( empty($_SESSION[$guid]['attendanceReasons']) || !is_array($_SESSION[$guid]['attendanceReasons']) ) {
        
        $unexcusedReasons = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceUnexcusedReasons') );
        $excusedReasons = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceExcusedReasons') );
        $medicalReasons = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceMedicalReasons') );

        $attendanceReasons = array_merge( array(' '), $unexcusedReasons, $medicalReasons, $excusedReasons);

        if (!empty($attendanceReasons)) {
            $_SESSION[$guid]['attendanceReasons'] = $attendanceReasons;
        }
    }

    echo "<select style='float: none; width: $width; margin-bottom: 3px' name='$name' id='$name'>";

    if (!empty($_SESSION[$guid]['attendanceReasons']) && is_array($_SESSION[$guid]['attendanceReasons'])) {
        foreach ($_SESSION[$guid]['attendanceReasons'] as $attendanceReason) {
            printf('<option value="%1$s" %2$s/>%1$s</option>', $attendanceReason, (($lastReason == $attendanceReason)? 'selected' : '' ) );
        }
    }

    echo '</select>';
}
