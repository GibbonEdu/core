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

use Gibbon\Domain\System\AlarmGateway;
use Gibbon\Http\Url;

//Gibbon system-wide includes
include './gibbon.php';

$gibbonAlarmID = $_GET['gibbonAlarmID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$URL = Url::fromRoute();

//Proceed!
if (empty($gibbonAlarmID) or empty($gibbonPersonID)) {
    header("Location: {$URL}");
} else {
    //Check alarm
    $alarmGateway = $container->get(AlarmGateway::class);

    $alarm = $alarmGateway->getByID($gibbonAlarmID);

    if (!empty($alarm)) {
        //Check confirmation of alarm
        $alarmConfirm = $alarmGateway->getAlarmConfirmationByPerson($alarm['gibbonAlarmID'], $gibbonPersonID);

        if (empty($alarmConfirm)) {
            //Insert confirmation
            $dataConfirm = ['gibbonAlarmID' => $alarm['gibbonAlarmID'], 'gibbonPersonID' => $gibbonPersonID, 'timestamp' => date('Y-m-d H:i:s')];
            $alarmGateway->insertAlarmConfirm($dataConfirm);
        }
    }

    //Success 0
    header("Location: {$URL}");
}
