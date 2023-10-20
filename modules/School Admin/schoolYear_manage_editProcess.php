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
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/schoolYear_manage_edit.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

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
            $row = $result->fetch();
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
            $name = $_POST['name'] ?? '';
            $status = $_POST['status'] ?? '';
            $sequenceNumber = $_POST['sequenceNumber'] ?? '';
            $firstDay = !empty($_POST['firstDay']) ? Format::dateConvert($_POST['firstDay']) : null;
            $lastDay = !empty($_POST['lastDay']) ? Format::dateConvert($_POST['lastDay']) : null;

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
                        // Enforces a single current school year by updating the status of other years
                        try {
                            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'sequenceNumber' => $sequenceNumber);
                            $sql = "UPDATE gibbonSchoolYear SET status = (CASE
                                WHEN sequenceNumber < :sequenceNumber THEN 'Past' ELSE 'Upcoming'
                            END) WHERE gibbonSchoolYearID <> :gibbonSchoolYearID";
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
                            $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'firstDay' => $firstDay, 'lastDay' => $lastDay, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                            $sql = "UPDATE gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        if ($firstDay > $row['firstDay']) {
                          $timetableDayDateGateway = $container->get(TimetableDayDateGateway::class);
                          $timetableDayDateGateway->deleteTTDatesInRange($row['firstDay'], $firstDay);
                        }

                        // Update session vars so the user is warned if they're logged into a different year
                        if ($status == 'Current') {
                            $session->set('gibbonSchoolYearIDCurrent', $gibbonSchoolYearID);
                            $session->set('gibbonSchoolYearNameCurrent', $name);
                            $session->set('gibbonSchoolYearSequenceNumberCurrent', $sequenceNumber);
                        }

                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
