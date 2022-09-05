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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/schoolYear_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'] ?? '';
    $status = $_POST['status'] ?? '';
    $sequenceNumber = $_POST['sequenceNumber'] ?? '';
    $firstDay = !empty($_POST['firstDay']) ? Format::dateConvert($_POST['firstDay']) : null;
    $lastDay = !empty($_POST['lastDay']) ? Format::dateConvert($_POST['lastDay']) : null;

    if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $firstDay == '' or $lastDay == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE name=:name OR sequenceNumber=:sequenceNumber';
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
                // Enforces a single current school year by updating the status of other years
                try {
                    $data = array('sequenceNumber' => $sequenceNumber);
                    $sql = "UPDATE gibbonSchoolYear SET status = (CASE
                        WHEN sequenceNumber < :sequenceNumber THEN 'Past' ELSE 'Upcoming'
                    END)";
                    $resultUpdate = $connection2->prepare($sql);
                    $resultUpdate->execute($data);
                } catch (PDOException $e) {
                    $currentFail = true;
                }
            }

            if ($currentFail) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                //Write to database
                try {
                    $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'firstDay' => $firstDay, 'lastDay' => $lastDay);
                    $sql = "INSERT INTO gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 3, '0', STR_PAD_LEFT);

                // Update session vars so the user is warned if they're logged into a different year
                if ($status == 'Current') {
                    $session->set('gibbonSchoolYearIDCurrent', $AI);
                    $session->set('gibbonSchoolYearNameCurrent', $name);
                    $session->set('gibbonSchoolYearSequenceNumberCurrent', $sequenceNumber);
                }

                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
            }
        }
    }
}
