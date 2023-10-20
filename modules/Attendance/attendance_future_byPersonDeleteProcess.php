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

use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
include './moduleFunctions.php';

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonAttendanceLogPersonID = $_GET['gibbonAttendanceLogPersonID'] ?? '';

$urlParams = [
    'target' => $_GET['target'] ?? '',
    'gibbonActivityID' => $_GET['gibbonActivityID'] ?? '',
    'gibbonGroupID' => $_GET['gibbonGroupID'] ?? '',
    'absenceType' => $_GET['absenceType'] ?? 'full',
    'date' => $_GET['date'] ?? '',
    'timeStart' => $_GET['timeStart'] ?? '',
    'timeEnd' => $_GET['timeEnd'] ?? '',
];

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Attendance/attendance_future_byPerson.php&gibbonPersonID=$gibbonPersonID&".http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_future_byPerson.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if planner specified
    if ($gibbonPersonID == '' or $gibbonAttendanceLogPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //UPDATE
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID);
            $sql = 'DELETE FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Success 0
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
