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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Module\Attendance\AttendanceView;

//Get's a count of absent days for specified student between specified dates (YYYY-MM-DD, inclusive). Return of FALSE means there was an error, or no data
function getAbsenceCount($guid, $gibbonPersonID, $connection2, $dateStart, $dateEnd, $gibbonCourseClassID = 0)
{
    $queryFail = false;

    global $gibbon, $session, $pdo, $container;

    $settingGateway = $container->get(SettingGateway::class);
    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.

    if (!empty($gibbonCourseClassID)) {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonCourseClassID' => $gibbonCourseClassID);
        $sql = "SELECT gibbonAttendanceLogPerson.*, gibbonSchoolYearSpecialDay.type AS specialDay FROM gibbonAttendanceLogPerson
                LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=gibbonAttendanceLogPerson.date AND gibbonSchoolYearSpecialDay.type='School Closure')
            WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPerson.context='Class' AND gibbonCourseClassID=:gibbonCourseClassID AND (gibbonAttendanceLogPerson.date BETWEEN :dateStart AND :dateEnd)  
            ORDER BY gibbonAttendanceLogPerson.date, timestampTaken";
    } else {
        $countClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);
        $sql = "SELECT gibbonAttendanceLogPerson.*, gibbonSchoolYearSpecialDay.type AS specialDay
                FROM gibbonAttendanceLogPerson
                LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.date=gibbonAttendanceLogPerson.date AND gibbonSchoolYearSpecialDay.type='School Closure')
                WHERE gibbonPersonID=:gibbonPersonID
                AND (gibbonAttendanceLogPerson.date BETWEEN :dateStart AND :dateEnd) ";
                if ($countClassAsSchool == "N") {
                    $sql .= ' AND NOT gibbonAttendanceLogPerson.context=\'Class\'';
                }
                $sql .= " ORDER BY gibbonAttendanceLogPerson.date, timestampTaken";
    }
    $result = $pdo->select($sql, $data);
    
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
    $timestamp = Format::timestamp($date);
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
    global $container;

    $queryFail = false;

    //Get all records for the student, in the date range specified, ordered by date and timestamp taken.
    try {
        $countClassAsSchool = $container->get(SettingGateway::class)->getSettingByScope('Attendance', 'countClassAsSchool');
        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);
        $sql = "SELECT count(*) AS count
                FROM gibbonAttendanceLogPerson p, gibbonAttendanceCode c
                WHERE (c.scope='Onsite - Late' OR c.scope='Offsite - Late')
                AND p.gibbonPersonID=:gibbonPersonID
                AND p.date>=:dateStart
                AND p.date<=:dateEnd
                AND p.type=c.name";
                if ($countClassAsSchool == "N") {
                    $sql .= ' AND NOT context=\'Class\'';
                }
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

    $return[] = '153, 102, 255';
    $return[] = '255, 99, 132';
    $return[] = '54, 162, 235';
    $return[] = '255, 206, 86';
    $return[] = '75, 192, 192';
    $return[] = '255, 159, 64';
    $return[] = '152, 221, 95';

    return $return;
}
