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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Module includes
include './moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceBooking_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        $foreignKey = $_POST['foreignKey'];
        $foreignKeyID = $_POST['foreignKeyID'];
        $dates = $_POST['dates'];
        $timeStart = $_POST['timeStart'];
        $timeEnd = $_POST['timeEnd'];
        $repeat = $_POST['repeat'];
        $repeatDaily = null;
        $repeatWeekly = null;
        if ($repeat == 'Daily') {
            $repeatDaily = $_POST['repeatDaily'];
        } elseif ($repeat == 'Weekly') {
            $repeatWeekly = $_POST['repeatWeekly'];
        }

        //Validate Inputs
        if ($foreignKey == '' or $foreignKeyID == '' or $timeStart == '' or $timeEnd == '' or $repeat == '' or count($dates) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Lock tables
            try {
                $sql = 'LOCK TABLE gibbonDaysOfWeek WRITE, gibbonSchoolYear WRITE, gibbonSchoolYearSpecialDay WRITE, gibbonSchoolYearTerm WRITE, gibbonTTColumnRow WRITE, gibbonTTDay WRITE, gibbonTTDayDate WRITE, gibbonTTDayRowClass WRITE, gibbonTTSpaceBooking WRITE, gibbonTTSpaceChange WRITE';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $failCount = 0;
            $available = '';
            //Scroll through all dates
            foreach ($dates as $date) {
                $available = isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd);
                if ($available == false) {
                    ++$failCount;
                } else {
                    //Write to database
                    try {
                        $data = array('foreignKey' => $foreignKey, 'foreignKeyID' => $foreignKeyID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'INSERT INTO gibbonTTSpaceBooking SET foreignKey=:foreignKey, foreignKeyID=:foreignKeyID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        ++$failCount;
                    }
                }
            }

            $successCount = count($dates) - $failCount;

            //Unlock locked database tables
            try {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
            }

            if ($successCount == 0) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } elseif ($successCount < count($dates)) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
