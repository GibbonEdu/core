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

//Gibbon system-wide includes
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
include './moduleFunctions.php';

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/attendance_studentSelfRegister.php";

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_studentSelfRegister.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $settingGateway = $container->get(SettingGateway::class);
    $studentSelfRegistrationIPAddresses = $settingGateway->getSettingByScope('Attendance', 'studentSelfRegistrationIPAddresses');
    $realIP = getIPAddress();
    if ($studentSelfRegistrationIPAddresses == '' || is_null($studentSelfRegistrationIPAddresses)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Check if school day
        $currentDate = date('Y-m-d');
        if (isSchoolOpen($guid, $currentDate, $connection2, true) == false) {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        }
        else {
            //Check for existence of records today
            try {
                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'date' => $currentDate);
                $sql = "SELECT type FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date=:date ORDER BY timestampTaken DESC";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() > 0) { //Records! Return error
                $URL .= '&return=error1';
                header("Location: {$URL}");
            }
            else { //If no records, set status to Present
                $inRange = false ;
                foreach (explode(',', $studentSelfRegistrationIPAddresses) as $ipAddress) {
                    if (trim($ipAddress) == $realIP)
                        $inRange = true ;
                }

                $status = $_POST['status'] ?? null;

                if (!$inRange && $status == 'Absent') {
                    try {
                        $dataUpdate = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPersonIDTaker' => $session->get('gibbonPersonID'), 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                        $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=\'Absent\'), gibbonPersonID=:gibbonPersonID, direction=\'Out\', type=\'Absent\', context=\'Self Registration\', reason=\'\', comment=\'\', gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=NULL, date=:date, timestampTaken=:timestampTaken';
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                }
                else if ($inRange && $status == 'Present') {
                    try {
                        $dataUpdate = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPersonIDTaker' => $session->get('gibbonPersonID'), 'date' => $currentDate, 'timestampTaken' => date('Y-m-d H:i:s'));
                        $sqlUpdate = 'INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=\'Present\'), gibbonPersonID=:gibbonPersonID, direction=\'In\', type=\'Present\', context=\'Self Registration\', reason=\'\', comment=\'\', gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=NULL, date=:date, timestampTaken=:timestampTaken';
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                }
                else {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                    exit();
                }

                $selfRegistrationRedirect = $settingGateway->getSettingByScope('Attendance', 'selfRegistrationRedirect');
                if ($selfRegistrationRedirect == 'Y') {
                    $URL = $session->get('absoluteURL')."/index.php?q=/modules/Messenger/messageWall_view.php&return=message0&status=$status";
                }
                else {
                    $URL .= '&return=success0';
                }
                header("Location: {$URL}");
                exit();
            }
        }
    }
}
