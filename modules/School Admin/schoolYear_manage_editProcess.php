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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/schoolYear_manage_edit.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonSchoolYearID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            $name = $_POST['name'];
            $status = $_POST['status'];
            $sequenceNumber = $_POST['sequenceNumber'];
            $firstDay = dateConvert($guid, $_POST['firstDay']);
            $lastDay = dateConvert($guid, $_POST['lastDay']);

            if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $firstDay == '' or $lastDay == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                    $sql = 'SELECT * FROM gibbonSchoolYear WHERE (name=:name OR sequenceNumber=:sequenceNumber) AND NOT gibbonSchoolYearID=:gibbonSchoolYearID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Check for other currents
                    $currentFail = false;
                    if ($status == 'Current') {
                        try {
                            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                            $sql = "SELECT gibbonSchoolYearID, sequenceNumber FROM gibbonSchoolYear WHERE status='Current' AND NOT gibbonSchoolYearID=:gibbonSchoolYearID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }
                        if ($result->rowCount() > 0) {
                            // Enforces a single current school year by updating the status of the previous current year
                            while ($currentSchoolYear = $result->fetch()) {
                                $direction = ($sequenceNumber < $currentSchoolYear['sequenceNumber'])? 'Upcoming' : 'Past';
                                try {
                                    $data = array('gibbonSchoolYearID' => $currentSchoolYear['gibbonSchoolYearID'], 'status' => $direction, 'sequenceNumber' => $sequenceNumber);
                                    $sql = "UPDATE gibbonSchoolYear SET status = (CASE
                                        WHEN gibbonSchoolYearID=:gibbonSchoolYearID THEN :status
                                        WHEN sequenceNumber < :sequenceNumber THEN 'Past'
                                        ELSE 'Upcoming'
                                    END)";
                                    $resultUpdate = $connection2->prepare($sql);
                                    $resultUpdate->execute($data);
                                } catch (PDOException $e) {
                                    $currentFail = true;
                                }
                            }
                        }
                    }

                    if ($currentFail) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    } else {
                        //Write to database
                        try {
                            $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'firstDay' => $firstDay, 'lastDay' => $lastDay, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                            $sql = "UPDATE gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        // Update session vars so the user is warned if they're logged into a different year
                        if ($status == 'Current') {
                            $_SESSION[$guid]['gibbonSchoolYearIDCurrent'] = $gibbonSchoolYearID;
                            $_SESSION[$guid]['gibbonSchoolYearNameCurrent'] = $name;
                            $_SESSION[$guid]['gibbonSchoolYearSequenceNumberCurrent'] = $sequenceNumber;
                        }

                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
